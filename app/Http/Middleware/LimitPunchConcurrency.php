<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LimitPunchConcurrency
{
    private const SLOTS = 6;

    private const MAX_WAIT_SECONDS = 30;

    private const DB_WAIT_SECONDS = 10;

    public function handle(Request $request, Closure $next): Response
    {
        $dir = storage_path('app/locks');

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $handle = null;
        $deadline = microtime(true) + self::MAX_WAIT_SECONDS;

        while ($handle === null && microtime(true) < $deadline) {
            for ($i = 0; $i < self::SLOTS; $i++) {
                $fp = @fopen($dir.'/punch-'.$i.'.lock', 'c');

                if ($fp === false) {
                    continue;
                }

                if (flock($fp, LOCK_EX | LOCK_NB)) {
                    $handle = $fp;
                    break;
                }

                fclose($fp);
            }

            if ($handle === null) {
                usleep(random_int(20000, 60000));
            }
        }

        if ($handle === null) {
            return $this->busy($request);
        }

        try {
            if (! $this->databaseReady()) {
                return $this->busy($request);
            }

            return $next($request);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function databaseReady(): bool
    {
        $deadline = microtime(true) + self::DB_WAIT_SECONDS;

        do {
            try {
                DB::connection()->getPdo();

                return true;
            } catch (QueryException $e) {
                if (! str_contains($e->getMessage(), '2002') && ! str_contains($e->getMessage(), '1040')) {
                    return true;
                }
            }

            usleep(random_int(40000, 140000));
        } while (microtime(true) < $deadline);

        return false;
    }

    private function busy(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => 'The time clock is busy. Please tap your punch again.',
            ], 503);
        }

        return back()->with('punch_error', 'The time clock is busy. Please tap your punch again.');
    }
}

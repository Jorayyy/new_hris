<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class OperationsController extends Controller
{
    public function index(): View
    {
        $backupRoot = storage_path('app/backups');
        $backups = File::isDirectory($backupRoot)
            ? collect(File::directories($backupRoot))
                ->sortDesc()
                ->take(5)
                ->map(fn (string $path) => [
                    'name' => basename($path),
                    'size' => $this->humanSize(collect(File::allFiles($path))->sum(fn ($file) => $file->getSize())),
                ])
                ->values()
            : collect();

        return view('operations.index', [
            'pendingJobs' => DB::table('jobs')->count(),
            'failedJobs' => DB::table('failed_jobs')->count(),
            'recentFailures' => DB::table('failed_jobs')->latest('failed_at')->limit(5)->get(['queue', 'failed_at', 'exception']),
            'scheduleLastRun' => Cache::get('schedule_last_run'),
            'backups' => $backups,
            'checks' => $this->checks(),
        ]);
    }

    public function retryFailed(): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => ['all']]);
        AuditLog::record('retry', 'Queued retry for all failed jobs');

        return back()->with('ok', 'Retry queued for all failed jobs. Start a worker to process them.');
    }

    /**
     * @return array<int, array{label: string, value: string, state: string}>
     */
    private function checks(): array
    {
        $production = app()->isProduction();
        $lastRun = Cache::get('schedule_last_run');
        $schedulerStale = ! $lastRun || now()->diffInMinutes($lastRun, true) > 5;

        return [
            ['Environment', app()->environment(), $production ? 'ok' : 'warn'],
            ['Debug mode', config('app.debug') ? 'On' : 'Off', $production && config('app.debug') ? 'warn' : 'ok'],
            ['HTTPS', request()->isSecure() ? 'Yes' : 'No', $production && ! request()->isSecure() ? 'warn' : 'ok'],
            ['Queue connection', (string) config('queue.default'), $production && in_array(config('queue.default'), ['sync', 'null'], true) ? 'warn' : 'ok'],
            ['Scheduler last run', $lastRun ? Carbon::parse($lastRun)->diffForHumans() : 'Never recorded', $schedulerStale && $production ? 'warn' : 'ok'],
            ['Cache store', (string) config('cache.default'), 'ok'],
            ['Mailer', (string) config('mail.default'), $production && config('mail.default') === 'log' ? 'warn' : 'ok'],
            ['Timezone', (string) config('app.timezone'), 'ok'],
            ['Database', (string) DB::connection()->getDriverName(), 'ok'],
            ['Private files', File::isDirectory(storage_path('app/private')) ? 'Writable' : 'Missing', File::isDirectory(storage_path('app/private')) ? 'ok' : 'warn'],
            ['PHP / Laravel', PHP_VERSION.' / '.app()->version(), 'ok'],
        ];
    }

    private function humanSize(int $bytes): string
    {
        return match (true) {
            $bytes >= 1073741824 => number_format($bytes / 1073741824, 1).' GB',
            $bytes >= 1048576 => number_format($bytes / 1048576, 1).' MB',
            $bytes >= 1024 => number_format($bytes / 1024, 0).' KB',
            default => $bytes.' B',
        };
    }
}

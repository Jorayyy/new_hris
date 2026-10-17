<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupCommand extends Command
{
    protected $signature = 'hris:backup
        {--keep=7 : Days of backups to keep}
        {--path= : Destination directory (defaults to storage/app/backups)}';

    protected $description = 'Dump the database and archive private employee files';

    public function handle(): int
    {
        $root = $this->option('path') ?: storage_path('app/backups');
        $dir = $root.'/'.now()->format('Y-m-d_His');

        File::ensureDirectoryExists($dir);

        if (! $this->dumpDatabase($dir)) {
            File::deleteDirectory($dir);

            return self::FAILURE;
        }

        $this->archiveFiles($dir);
        $this->prune($root, (int) $this->option('keep'));

        $this->info('Backup written to '.$dir);

        return self::SUCCESS;
    }

    private function dumpDatabase(string $dir): bool
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $database = $connection->getDatabaseName();

            if ($database === ':memory:' || ! is_file($database)) {
                $this->warn('In-memory SQLite database — skipped the database dump.');

                return true;
            }

            File::copy($database, $dir.'/database.sqlite');

            return true;
        }

        $config = $connection->getConfig();
        $target = $dir.'/database.sql';

        [$command, $passwordKey] = match ($driver) {
            'mysql', 'mariadb' => [[
                'mysqldump', '--host='.$config['host'], '--port='.$config['port'],
                '--user='.$config['username'], '--single-transaction', '--routines',
                '--result-file='.$target, $config['database'],
            ], 'MYSQL_PWD'],
            'pgsql' => [[
                'pg_dump', '--host='.$config['host'], '--port='.$config['port'],
                '--username='.$config['username'], '--file='.$target, $config['database'],
            ], 'PGPASSWORD'],
            default => [null, null],
        };

        if ($command === null) {
            $this->error('Unsupported database driver: '.$driver);

            return false;
        }

        $process = new Process($command, null, [$passwordKey => (string) $config['password']]);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error(trim($process->getErrorOutput()) ?: 'Database dump failed. Is '.$command[0].' installed?');

            return false;
        }

        return true;
    }

    private function archiveFiles(string $dir): void
    {
        $source = storage_path('app/private');

        if (! File::isDirectory($source)) {
            return;
        }

        $zip = new ZipArchive;

        if ($zip->open($dir.'/files.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->warn('Could not create the file archive.');

            return;
        }

        foreach (File::allFiles($source) as $file) {
            $zip->addFile($file->getPathname(), $file->getRelativePathname());
        }

        $zip->close();
    }

    private function prune(string $root, int $keepDays): void
    {
        $cutoff = now()->subDays(max(1, $keepDays));

        foreach (File::directories($root) as $path) {
            $createdAt = Carbon::createFromFormat('Y-m-d_His', basename($path));

            if ($createdAt instanceof Carbon && $createdAt->lessThan($cutoff)) {
                File::deleteDirectory($path);
            }
        }
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('app/testing-backups');
        File::deleteDirectory($this->root);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_backup_creates_archive_and_prunes_old_backups(): void
    {
        File::ensureDirectoryExists($this->root.'/'.now()->subDays(30)->format('Y-m-d_His'));

        $this->artisan('hris:backup', ['--path' => $this->root])
            ->expectsOutputToContain('Backup written to')
            ->assertSuccessful();

        $directories = File::directories($this->root);

        $this->assertCount(1, $directories);
        $this->assertFileExists($directories[0].'/files.zip');
    }

    public function test_keep_option_retains_recent_backups(): void
    {
        $recent = $this->root.'/'.now()->subDays(3)->format('Y-m-d_His');
        File::ensureDirectoryExists($recent);

        $this->artisan('hris:backup', ['--path' => $this->root, '--keep' => 30])->assertSuccessful();

        $this->assertDirectoryExists($recent);
    }
}

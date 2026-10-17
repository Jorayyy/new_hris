<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $this->moveFiles('employee_documents', 'public', 'local');
        $this->moveFiles('disciplinary_case_actions', 'public', 'local');
    }

    public function down(): void
    {
        $this->moveFiles('employee_documents', 'local', 'public');
        $this->moveFiles('disciplinary_case_actions', 'local', 'public');
    }

    private function moveFiles(string $table, string $from, string $to): void
    {
        $source = Storage::disk($from);
        $target = Storage::disk($to);

        foreach (DB::table($table)->whereNotNull('file_path')->pluck('file_path') as $path) {
            if ($source->exists($path) && ! $target->exists($path)) {
                $target->put($path, $source->get($path));
                $source->delete($path);
            }
        }
    }
};

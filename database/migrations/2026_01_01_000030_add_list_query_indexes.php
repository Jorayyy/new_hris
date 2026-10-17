<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns filtered or sorted on by list/report pages that no existing index covers.
     *
     * @var array<int, array{0: string, 1: array<int, string>}>
     */
    private array $indexes = [
        ['payslips', ['employee_id']],
        ['attendances', ['work_date']],
        ['attendances', ['branch_id']],
        ['audit_logs', ['created_at']],
        ['qa_evaluations', ['branch_id']],
        ['notifications', ['notifiable_type', 'notifiable_id', 'read_at']],
        ['employee_documents', ['expires_at']],
        ['employee_loans', ['employee_id']],
        ['leave_requests', ['created_at']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as [$table, $columns]) {
            if (Schema::hasIndex($table, $columns)) {
                continue;
            }

            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns));
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as [$table, $columns]) {
            if (Schema::hasIndex($table, $columns)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($columns));
            }
        }
    }
};

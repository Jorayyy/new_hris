<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('filed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30);
            $table->string('severity', 10)->default('medium');
            $table->string('status', 20)->default('open');
            $table->date('incident_date')->nullable();
            $table->string('subject');
            $table->text('details')->nullable();
            $table->date('response_due_on')->nullable();
            $table->text('decision')->nullable();
            $table->string('sanction')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('disciplinary_case_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciplinary_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('note');
            $table->text('body');
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_case_actions');
        Schema::dropIfExists('disciplinary_cases');
    }
};

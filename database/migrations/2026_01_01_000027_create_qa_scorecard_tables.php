<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qa_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('weight', 5, 2)->default(100);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('qa_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('evaluated_on');
            $table->string('reference')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->text('coaching_notes')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'evaluated_on']);
        });

        Schema::create('qa_evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qa_evaluation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qa_criterion_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2);
            $table->timestamps();
            $table->unique(['qa_evaluation_id', 'qa_criterion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qa_evaluation_scores');
        Schema::dropIfExists('qa_evaluations');
        Schema::dropIfExists('qa_criteria');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('interest_rate', 6, 4)->default(0);
            $table->boolean('is_government')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_type_id')->constrained()->cascadeOnDelete();
            $table->string('reference_no')->nullable();
            $table->decimal('principal', 12, 2);
            $table->decimal('monthly_amortization', 12, 2);
            $table->decimal('balance', 12, 2);
            $table->date('start_date')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_run_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_on')->nullable();
            $table->timestamps();
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('loan_deductions_total', 12, 2)->default(0)->after('other_deductions_total');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn('loan_deductions_total');
        });

        Schema::dropIfExists('loan_payments');
        Schema::dropIfExists('employee_loans');
        Schema::dropIfExists('loan_types');
    }
};

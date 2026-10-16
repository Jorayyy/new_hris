<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sss_contributions', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_msc', 12, 2);
            $table->decimal('max_msc', 12, 2);
            $table->decimal('employee_share', 10, 2);
            $table->decimal('employer_share', 10, 2);
            $table->decimal('ec_share', 10, 2)->default(10);
            $table->timestamps();
        });

        Schema::create('philhealth_contributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->decimal('min_salary', 12, 2);
            $table->decimal('max_salary', 12, 2);
            $table->decimal('employee_rate', 6, 4);
            $table->decimal('employer_rate', 6, 4);
            $table->timestamps();
        });

        Schema::create('pagibig_contributions', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_salary', 12, 2);
            $table->decimal('max_salary', 12, 2)->nullable();
            $table->decimal('employee_rate', 6, 4);
            $table->decimal('employer_rate', 6, 4);
            $table->decimal('employee_cap', 10, 2);
            $table->decimal('employer_cap', 10, 2);
            $table->timestamps();
        });

        Schema::create('tax_tables', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('period')->default('semi_monthly');
            $table->decimal('min_taxable', 12, 2);
            $table->decimal('max_taxable', 12, 2)->nullable();
            $table->decimal('base_tax', 12, 2)->default(0);
            $table->decimal('rate', 6, 4)->default(0);
            $table->decimal('excess_over', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_tables');
        Schema::dropIfExists('pagibig_contributions');
        Schema::dropIfExists('philhealth_contributions');
        Schema::dropIfExists('sss_contributions');
    }
};

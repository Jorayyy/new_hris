<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedTinyInteger('probation_months')->default(6)->after('employment_status');
        });

        Schema::create('clearance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->boolean('is_done')->default(false);
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('done_at')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['employee_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_items');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('probation_months');
        });
    }
};

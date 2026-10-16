<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->dateTime('time_in')->nullable();
            $table->dateTime('break1_out')->nullable();
            $table->dateTime('break1_in')->nullable();
            $table->dateTime('lunch_out')->nullable();
            $table->dateTime('lunch_in')->nullable();
            $table->dateTime('break2_out')->nullable();
            $table->dateTime('break2_in')->nullable();
            $table->dateTime('time_out')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};

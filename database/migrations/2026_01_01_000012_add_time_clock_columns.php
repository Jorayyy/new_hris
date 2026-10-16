<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('pin')->nullable()->after('employee_no');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dateTime('break1_out')->nullable()->after('time_in');
            $table->dateTime('break1_in')->nullable()->after('break1_out');
            $table->dateTime('lunch_out')->nullable()->after('break1_in');
            $table->dateTime('lunch_in')->nullable()->after('lunch_out');
            $table->dateTime('break2_out')->nullable()->after('lunch_in');
            $table->dateTime('break2_in')->nullable()->after('break2_out');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['break1_out', 'break1_in', 'lunch_out', 'lunch_in', 'break2_out', 'break2_in']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('pin');
        });
    }
};

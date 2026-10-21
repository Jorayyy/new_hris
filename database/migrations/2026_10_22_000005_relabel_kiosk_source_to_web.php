<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('attendances')->where('source', 'kiosk')->update(['source' => 'web']);
    }

    public function down(): void
    {
        DB::table('attendances')->where('source', 'web')->update(['source' => 'kiosk']);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_required')->default(false);
            $table->boolean('requires_expiry')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('employee_documents', function (Blueprint $table) {
            $table->foreignId('document_type_id')->nullable()->after('employee_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_type_id');
        });

        Schema::dropIfExists('document_types');
    }
};

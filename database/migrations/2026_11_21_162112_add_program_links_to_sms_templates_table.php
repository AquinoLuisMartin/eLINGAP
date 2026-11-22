<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_templates', function (Blueprint $table): void {
            $table->foreignId('program_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('superseded_by_id')->nullable()->constrained('sms_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sms_templates', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('superseded_by_id');
            $table->dropUnique(['program_id']);
            $table->dropConstrainedForeignId('program_id');
        });
    }
};

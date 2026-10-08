<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_blasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('barangay_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('excluded_count')->default(0);
            $table->timestampsTz();
        });

        Schema::table('sms_messages', function (Blueprint $table) {
            $table->foreignId('sms_blast_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sms_messages', fn (Blueprint $table) => $table->dropConstrainedForeignId('sms_blast_id'));
        Schema::dropIfExists('sms_blasts');
    }
};

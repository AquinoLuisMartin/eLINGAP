<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('senior_citizen_id_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('senior_citizen_id')->unique()->constrained()->restrictOnDelete();
            $table->char('verification_token_hash', 64)->unique();
            $table->text('verification_token');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('senior_citizen_id_credentials');
    }
};

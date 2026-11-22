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
        Schema::table('benefit_payments', function (Blueprint $table) {
            $table->string('claimant_name', 350)->nullable();
            $table->string('claimant_relationship', 100)->nullable();
            $table->string('claimant_contact', 30)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('benefit_payments', function (Blueprint $table) {
            $table->dropColumn(['claimant_name', 'claimant_relationship', 'claimant_contact']);
        });
    }
};

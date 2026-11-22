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
            $table->boolean('identity_verified')->nullable();
            $table->boolean('authorization_verified')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('benefit_payments', function (Blueprint $table) {
            $table->dropColumn(['identity_verified', 'authorization_verified']);
        });
    }
};

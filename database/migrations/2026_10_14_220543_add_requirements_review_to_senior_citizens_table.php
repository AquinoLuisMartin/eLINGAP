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
        Schema::table('senior_citizens', function (Blueprint $table) {
            $table->json('requirements_checks')->nullable();
            $table->text('review_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('senior_citizens', function (Blueprint $table) {
            $table->dropColumn(['requirements_checks', 'review_reason']);
        });
    }
};

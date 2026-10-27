<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('senior_citizen_transfers', function (Blueprint $table) {
            $table->dropColumn('registration_number');
        });

        Schema::table('senior_citizens', function (Blueprint $table) {
            $table->dropColumn('registration_number');
        });
    }

    public function down(): void
    {
        // Restore the legacy columns without fabricating the removed values.
        Schema::table('senior_citizens', function (Blueprint $table) {
            $table->string('registration_number', 30)->nullable()->unique();
        });

        Schema::table('senior_citizen_transfers', function (Blueprint $table) {
            $table->string('registration_number', 30)->nullable();
        });
    }
};

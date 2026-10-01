<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_status_histories', function (Blueprint $table) {
            $table->index('application_id');
        });

        Schema::table('senior_citizen_histories', function (Blueprint $table) {
            $table->index('senior_citizen_id');
        });
    }

    public function down(): void
    {
        Schema::table('application_status_histories', function (Blueprint $table) {
            $table->dropIndex(['application_id']);
        });

        Schema::table('senior_citizen_histories', function (Blueprint $table) {
            $table->dropIndex(['senior_citizen_id']);
        });
    }
};

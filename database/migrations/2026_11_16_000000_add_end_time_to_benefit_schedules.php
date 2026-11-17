<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('benefit_schedules', function (Blueprint $table): void {
            $table->dateTimeTz('ends_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('benefit_schedules', function (Blueprint $table): void {
            $table->dropColumn('ends_at');
        });
    }
};

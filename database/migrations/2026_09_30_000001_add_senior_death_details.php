<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('senior_citizens', function (Blueprint $table) {
            $table->date('died_on')->nullable();
            $table->timestampTz('death_declared_at')->nullable();
            $table->foreignId('death_declared_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('senior_citizens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('death_declared_by');
            $table->dropColumn(['died_on', 'death_declared_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->string('claimant_type', 20)->nullable();
            $table->string('claimant_name', 200)->nullable();
            $table->string('claimant_relationship', 100)->nullable();
            $table->string('claimant_contact', 30)->nullable();
            $table->boolean('osca_id_checked')->default(false);
            $table->boolean('authorization_checked')->default(false);
            $table->boolean('representative_id_checked')->default(false);
            $table->text('reversal_reason')->nullable();
            $table->timestampTz('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn(['claimant_type', 'claimant_name', 'claimant_relationship', 'claimant_contact', 'osca_id_checked', 'authorization_checked', 'representative_id_checked', 'reversal_reason', 'reversed_at']);
        });
    }
};

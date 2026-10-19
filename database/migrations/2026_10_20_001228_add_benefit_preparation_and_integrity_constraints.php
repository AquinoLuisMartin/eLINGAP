<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('benefit_schedules', function (Blueprint $table) {
            $table->uuid('preparation_token')->nullable();
            $table->unsignedBigInteger('preparation_cursor')->default(0);
            $table->unsignedBigInteger('preparation_max_id')->default(0);
        });
        Schema::table('sms_delivery_logs', fn (Blueprint $table) => $table->index('recorded_at'));
        foreach ($this->constraints() as $table => $expression) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_integrity CHECK ({$expression}) NOT VALID");
            DB::statement("ALTER TABLE {$table} VALIDATE CONSTRAINT {$table}_integrity");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->constraints() as $table => $expression) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$table}_integrity");
        }
        Schema::table('benefit_schedules', fn (Blueprint $table) => $table->dropColumn(['preparation_token', 'preparation_cursor', 'preparation_max_id']));
        Schema::table('sms_delivery_logs', fn (Blueprint $table) => $table->dropIndex(['recorded_at']));
    }

    private function constraints(): array
    {
        return [
            'benefit_schedules' => "(amount IS NULL OR amount > 0) AND available_funds >= 0 AND status IN ('DRAFT', 'APPROVED', 'CLOSED', 'CANCELLED')",
            'benefit_entitlements' => "(amount IS NULL OR amount > 0) AND status IN ('PENDING', 'UNPAID', 'UNCLAIMED', 'CLAIMED')",
            'benefit_payments' => "amount > 0 AND claimant_type IN ('SELF', 'REPRESENTATIVE')",
            'benefit_schedule_recipients' => "outcome IN ('SELECTED', 'EXCLUDED', 'PENDING', 'CLAIMED', 'UNCLAIMED', 'REVERSED')",
        ];
    }
};

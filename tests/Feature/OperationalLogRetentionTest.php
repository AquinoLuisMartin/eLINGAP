<?php

namespace Tests\Feature;

use App\Models\LoginLog;
use App\Models\SmsDeliveryLog;
use App\Models\SmsMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalLogRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_preserves_history_and_pruning_keeps_recent_rows_and_message_records(): void
    {
        $this->freezeTime();
        config(['retention.login_logs_days' => 30, 'retention.sms_delivery_logs_days' => 30, 'retention.failed_jobs_days' => 0]);
        $oldLogin = LoginLog::create(['event' => 'LOGIN', 'success' => true]);
        $oldLogin->forceFill(['created_at' => now()->subDays(31)])->save();
        $recentLogin = LoginLog::create(['event' => 'LOGIN_FAILED', 'success' => false]);
        $recentLogin->forceFill(['created_at' => now()->subDays(29)])->save();
        $message = SmsMessage::create(['recipient_number' => '+639171234567', 'message' => 'Synthetic', 'status' => 'SENT']);
        $oldDelivery = SmsDeliveryLog::create(['sms_message_id' => $message->id, 'status' => 'SENT', 'recorded_at' => now()->subDays(31)]);
        $recentDelivery = SmsDeliveryLog::create(['sms_message_id' => $message->id, 'status' => 'SENT', 'recorded_at' => now()->subDays(29)]);

        $this->artisan('app:prune-operational-logs', ['--dry-run' => true])->assertSuccessful();
        $this->assertModelExists($oldLogin);
        $this->assertModelExists($oldDelivery);
        $this->artisan('app:prune-operational-logs')->assertSuccessful();
        $this->assertModelMissing($oldLogin);
        $this->assertModelMissing($oldDelivery);
        $this->assertModelExists($recentLogin);
        $this->assertModelExists($recentDelivery);
        $this->assertModelExists($message);
    }

    public function test_disabled_retention_preserves_old_logs(): void
    {
        config(['retention.login_logs_days' => 0, 'retention.sms_delivery_logs_days' => 0, 'retention.failed_jobs_days' => 0]);
        $login = LoginLog::create(['event' => 'LOGIN', 'success' => true]);
        $login->forceFill(['created_at' => now()->subYears(2)])->save();

        $this->artisan('app:prune-operational-logs')->assertSuccessful();

        $this->assertModelExists($login);
    }
}

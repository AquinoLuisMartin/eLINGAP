<?php

namespace Tests\Feature\Sms;

use App\Models\SmsMessage;
use App\Services\Sms\SmsService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class SmsBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_large_audiences_have_bounded_writes_and_jobs_contain_only_identifiers(): void
    {
        $messages = array_fill(0, 401, ['recipient_number' => '+639171234567', 'message' => 'Synthetic private batch message']);
        DB::connection()->enableQueryLog();

        app(SmsService::class)->queueMany($messages);

        $queries = DB::connection()->getQueryLog();
        DB::connection()->disableQueryLog();
        $writes = array_filter($queries, fn ($query) => str_starts_with(strtolower($query['query']), 'insert into'));
        $this->assertLessThanOrEqual(6, count($writes));
        $this->assertDatabaseCount('sms_messages', 401);
        $this->assertDatabaseCount('jobs', 401);
        $payloads = DB::table('jobs')->pluck('payload')->implode("\n");
        $this->assertStringNotContainsString('+639171234567', $payloads);
        $this->assertStringNotContainsString('Synthetic private batch message', $payloads);
        $this->assertSame(401, SmsMessage::where('status', 'QUEUED')->count());
    }

    public function test_queue_failure_rolls_back_all_message_rows(): void
    {
        DB::statement('ALTER TABLE jobs RENAME TO temporarily_unavailable_jobs');

        try {
            app(SmsService::class)->queueMany([['recipient_number' => '+639171234567', 'message' => 'Synthetic rollback batch']]);
            $this->fail('A failed queue write must roll back the message batch.');
        } catch (QueryException) {
            $this->assertDatabaseCount('sms_messages', 0);
        } finally {
            DB::statement('ALTER TABLE temporarily_unavailable_jobs RENAME TO jobs');
        }

        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_outer_transaction_rollback_removes_all_chunks_and_jobs(): void
    {
        try {
            DB::transaction(function (): void {
                app(SmsService::class)->queueMany(array_fill(0, 201, ['recipient_number' => '+639171234567', 'message' => 'Synthetic outer rollback']));
                throw new RuntimeException('Synthetic rollback');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic rollback', $exception->getMessage());
        }

        $this->assertDatabaseCount('sms_messages', 0);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_an_empty_audience_creates_no_messages_or_jobs(): void
    {
        app(SmsService::class)->queueMany([]);

        $this->assertDatabaseCount('sms_messages', 0);
        $this->assertDatabaseCount('jobs', 0);
    }
}

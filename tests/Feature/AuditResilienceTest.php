<?php

namespace Tests\Feature;

use App\Http\Middleware\AuditActivity;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\User;
use App\Services\Auth\PasswordRecovery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class AuditResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_id_previews_do_not_create_audit_rows(): void
    {
        Route::get('/audit-preview', fn () => response('Preview only'))->middleware(['web', AuditActivity::class])->name('senior-citizens.id-card');
        $this->actingAs(User::factory()->create())->get('/audit-preview')->assertSee('Preview only');
        $this->get('/audit-preview')->assertSee('Preview only');

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_logging_failure_uses_a_sanitized_fallback_and_preserves_the_response(): void
    {
        $staff = User::factory()->create();
        Route::get('/audit-resilience', fn () => response('Completed'))->middleware(['web', AuditActivity::class])->name('senior-citizens.document');
        Event::listen('eloquent.creating: '.AuditLog::class, fn () => throw new RuntimeException('Sensitive database detail'));
        Log::shouldReceive('error')->once()->andThrow(new RuntimeException('Sensitive logging detail'));
        Storage::fake('audit-fallback');
        $originalLog = ini_set('error_log', Storage::disk('audit-fallback')->path('fallback.log'));

        try {
            $this->actingAs($staff)->get('/audit-resilience')->assertSee('Completed');

            $fallback = Storage::disk('audit-fallback')->get('fallback.log');
            $this->assertStringContainsString('Page access audit and application logging are unavailable.', $fallback);
            $this->assertStringNotContainsString('Sensitive', $fallback);
        } finally {
            ini_set('error_log', $originalLog);
        }
    }

    public function test_business_audit_failure_still_rolls_back_the_protected_action(): void
    {
        $admin = User::factory()->admin()->create();
        $barangay = Barangay::create(['name' => 'Synthetic barangay', 'code' => 'SYN']);
        $senior = SeniorCitizen::create(['barangay_id' => $barangay->id, 'registration_number' => 'SC-SYN', 'first_name' => 'Synthetic', 'last_name' => 'Applicant', 'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Test address', 'status' => 'PENDING']);
        Event::listen('eloquent.creating: '.AuditLog::class, function (AuditLog $audit) {
            if ($audit->action === 'senior_citizen.archived') {
                throw new RuntimeException('Synthetic audit storage failure');
            }
        });
        Exceptions::fake([RuntimeException::class]);

        $this->actingAs($admin)->delete(route('administration.senior-citizens.destroy', $senior))->assertServerError();

        $this->assertSame('PENDING', $senior->fresh()->status->value);
        $this->assertDatabaseCount('senior_citizen_histories', 0);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'senior_citizen.archived']);
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_mutations_do_not_write_redundant_access_audits(): void
    {
        $staff = User::factory()->create();
        Route::post('/audit-resilience', function () {
            Program::create(['name' => 'Synthetic assistance', 'agency' => 'Test agency', 'budget' => 1000]);

            return redirect('/');
        })->middleware(['web', AuditActivity::class]);
        Event::listen('eloquent.creating: '.AuditLog::class, fn () => throw new RuntimeException('Sensitive database detail'));
        Log::shouldReceive('error')->never();

        $this->actingAs($staff)->post('/audit-resilience')->assertRedirect('/');

        $this->assertDatabaseHas('programs', ['name' => 'Synthetic assistance']);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_access_audit_failure_preserves_the_original_http_error(): void
    {
        $staff = User::factory()->create();
        Route::get('/audit-resilience', fn () => abort(409, 'Original conflict'))
            ->middleware(['web', AuditActivity::class])->name('senior-citizens.document');
        Event::listen('eloquent.creating: '.AuditLog::class, fn () => throw new RuntimeException('Sensitive database detail'));
        Log::shouldReceive('error')->once()->with('Page access audit could not be recorded.', ['failure_type' => RuntimeException::class]);

        $this->actingAs($staff)->get('/audit-resilience')->assertConflict()->assertSee('Original conflict');
    }

    public function test_private_document_access_still_records_the_operator(): void
    {
        $staff = User::factory()->create();
        Route::get('/audit-resilience', fn () => response('Completed'))
            ->middleware(['web', AuditActivity::class])->name('senior-citizens.document');

        $this->actingAs($staff)->get('/audit-resilience')->assertSee('Completed');

        $this->assertDatabaseHas('audit_logs', ['user_id' => $staff->id, 'action' => 'access.senior-citizens.document']);
    }

    public function test_routine_page_views_filters_and_refreshes_do_not_create_audit_rows(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('reports.index'))->assertOk();
        $this->get(route('reports.index'))->assertOk();
        $this->get(route('senior-citizens.index', ['search' => 'Synthetic']))->assertOk();
        $this->get(route('registrations.index'))->assertOk();

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_report_export_records_one_important_event_without_a_duplicate_access_log(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('reports.export', ['type' => 'masterlist']))->assertOk();

        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $staff->id, 'action' => 'report.exported']);
    }

    public function test_password_reset_audit_failure_rolls_back_the_password_change(): void
    {
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic administrator password']);
        $staff = User::factory()->create();
        $originalHash = $staff->password_hash;
        $password = Str::password();
        $service = app(PasswordRecovery::class);
        $reset = $service->request($staff->username);
        $code = $service->review($reset, $admin, 'approve', true, 'Synthetic administrator password');
        $auditCount = AuditLog::count();
        Event::listen('eloquent.creating: '.AuditLog::class, fn () => throw new RuntimeException('Synthetic audit failure'));
        Exceptions::fake([RuntimeException::class]);

        $this->post(route('password.update'), [
            'username' => $staff->username, 'recovery_code' => $code,
            'password' => $password, 'password_confirmation' => $password,
        ])->assertServerError();

        $this->assertSame($originalHash, $staff->fresh()->password_hash);
        $this->assertDatabaseCount('audit_logs', $auditCount);
        Exceptions::assertReported(RuntimeException::class);
    }
}

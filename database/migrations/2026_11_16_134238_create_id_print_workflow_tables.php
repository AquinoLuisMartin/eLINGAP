<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('id_reprint_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('senior_citizen_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('reason', 30);
            $table->text('details');
            $table->string('status', 20)->default('PENDING');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('review_reason')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('consumed_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'id']);
            $table->index(['senior_citizen_id', 'id']);
        });
        DB::statement("CREATE UNIQUE INDEX id_reprint_one_open ON id_reprint_requests (senior_citizen_id) WHERE status IN ('PENDING', 'APPROVED')");
        DB::statement("ALTER TABLE id_reprint_requests ADD CONSTRAINT id_reprint_status CHECK (status IN ('PENDING', 'APPROVED', 'REJECTED', 'CONSUMED'))");
        DB::statement("ALTER TABLE id_reprint_requests ADD CONSTRAINT id_reprint_review CHECK (status = 'PENDING' OR (reviewed_by IS NOT NULL AND reviewed_at IS NOT NULL))");
        DB::statement("ALTER TABLE id_reprint_requests ADD CONSTRAINT id_reprint_consumed CHECK (status != 'CONSUMED' OR consumed_at IS NOT NULL)");
        Schema::create('id_print_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('senior_citizen_id')->constrained()->restrictOnDelete();
            $table->foreignId('reprint_request_id')->nullable()->constrained('id_reprint_requests')->restrictOnDelete();
            $table->foreignId('legacy_audit_id')->nullable()->unique()->constrained('audit_logs')->restrictOnDelete();
            $table->string('purpose', 20);
            $table->string('status', 20)->default('GENERATED');
            $table->foreignId('generated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('generated_at');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('confirmed_at')->nullable();
            $table->foreignId('failed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestampsTz();
            $table->index(['senior_citizen_id', 'status', 'id']);
        });
        DB::statement("CREATE UNIQUE INDEX id_print_one_generated ON id_print_attempts (senior_citizen_id) WHERE status = 'GENERATED'");
        DB::statement("ALTER TABLE id_print_attempts ADD CONSTRAINT id_print_status CHECK (status IN ('GENERATED', 'CONFIRMED', 'FAILED'))");
        DB::statement("ALTER TABLE id_print_attempts ADD CONSTRAINT id_print_confirmation CHECK (status != 'CONFIRMED' OR (confirmed_at IS NOT NULL AND confirmed_by IS NOT NULL))");
        DB::statement("ALTER TABLE id_print_attempts ADD CONSTRAINT id_print_failure CHECK (status != 'FAILED' OR (failed_at IS NOT NULL AND failed_by IS NOT NULL AND failure_reason IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('id_print_attempts');
        Schema::dropIfExists('id_reprint_requests');
    }
};

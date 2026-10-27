<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('LOCK TABLE users IN ACCESS EXCLUSIVE MODE');
        if (DB::table('users')->selectRaw('lower(username)')->groupByRaw('lower(username)')->havingRaw('count(*) > 1')->exists()) {
            throw new RuntimeException('Resolve case-insensitive username collisions before applying this migration; no usernames were changed.');
        }
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false);
            $table->unsignedInteger('credential_version')->default(1);
        });
        DB::statement('CREATE UNIQUE INDEX uq_users_username_lower ON users (lower(username))');
        DB::statement('DROP INDEX IF EXISTS idx_users_username_lower');
        DB::statement('ALTER TABLE users ADD CONSTRAINT chk_users_credential_version CHECK (credential_version > 0)');
        Schema::create('password_reset_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('PENDING');
            $table->timestampTz('requested_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('identity_verified_at')->nullable();
            $table->text('recovery_code_hash')->nullable();
            $table->timestampTz('recovery_expires_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->unsignedSmallInteger('failed_attempts')->default(0);
            $table->timestampsTz();
            $table->index(['status', 'requested_at', 'id'], 'idx_password_requests_review');
        });
        DB::statement("ALTER TABLE password_reset_requests ADD CONSTRAINT chk_password_request_status CHECK (status IN ('PENDING','APPROVED','REJECTED','COMPLETED','EXPIRED'))");
        DB::statement('ALTER TABLE password_reset_requests ADD CONSTRAINT chk_password_request_attempts CHECK (failed_attempts BETWEEN 0 AND 5)');
        DB::statement("ALTER TABLE password_reset_requests ADD CONSTRAINT chk_password_request_approval CHECK (status != 'APPROVED' OR (recovery_code_hash IS NOT NULL AND recovery_expires_at IS NOT NULL AND identity_verified_at IS NOT NULL AND reviewed_by IS NOT NULL AND reviewed_at IS NOT NULL))");
        DB::statement("CREATE UNIQUE INDEX uq_password_requests_open_user ON password_reset_requests (user_id) WHERE status IN ('PENDING','APPROVED')");
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_requests');
        DB::statement('DROP INDEX IF EXISTS uq_users_username_lower');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_users_username_lower ON users (lower(username))');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS chk_users_credential_version');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['must_change_password', 'credential_version']));
    }
};

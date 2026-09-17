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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique('roles_name_key');
            $table->text('description')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        // Role names are a closed set mirrored by the UserRole enum.
        DB::statement("alter table roles add constraint chk_roles_name check (name in ('ADMIN', 'OSCA_STAFF'))");

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles', indexName: 'fk_users_role')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('username', 100)->unique('uq_users_username');
            $table->string('email')->nullable()->unique('uq_users_email');
            $table->text('password_hash');
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('name_suffix', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_login_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('role_id', 'idx_users_role_id');
            $table->index('is_active', 'idx_users_is_active');
        });

        DB::statement("alter table users add constraint chk_users_username check (length(trim(username)) >= 3)");
        DB::statement("alter table users add constraint chk_users_email check (email is null or email ~* '^[A-Z0-9._%+-]+@[A-Z0-9.-]+\\.[A-Z]{2,}\$')");

        // Supports case-insensitive username lookups during authentication.
        DB::statement('create index idx_users_username_lower on users (lower(username))');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};

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
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users', indexName: 'fk_login_user')->cascadeOnUpdate()->nullOnDelete();
            $table->string('username_attempted', 100)->nullable();
            $table->string('event', 30);
            $table->ipAddress()->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('success');
            $table->text('failure_reason')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index('user_id', 'idx_login_user');
            $table->index('created_at', 'idx_login_created_at');
            $table->index('event', 'idx_login_event');
            $table->index('username_attempted', 'idx_login_username_attempted');
        });

        // Event names are a closed set mirrored by the LoginEvent enum.
        DB::statement("alter table login_logs add constraint chk_login_event check (event in ('LOGIN', 'LOGOUT', 'LOGIN_FAILED', 'PASSWORD_CHANGED', 'PASSWORD_RESET'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};

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
        if (DB::table('users')->whereRaw("username !~ '^[0-9]{4}-[0-9]{4}$'")->exists()) {
            throw new RuntimeException('Assign official employee IDs to every account with app:assign-employee-ids before switching access; no accounts were changed.');
        }

        Schema::table('users', fn (Blueprint $table) => $table->renameColumn('username', 'employee_id'));
        DB::statement('ALTER TABLE users RENAME CONSTRAINT uq_users_username TO uq_users_employee_id');
        DB::statement('DROP INDEX uq_users_username_lower');
        DB::statement('ALTER TABLE users DROP CONSTRAINT chk_users_username');
        DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_employee_id CHECK (employee_id ~ '^[0-9]{4}-[0-9]{4}$')");
        Schema::table('login_logs', function (Blueprint $table) {
            $table->renameColumn('username_attempted', 'employee_id_attempted');
            $table->renameIndex('idx_login_username_attempted', 'idx_login_employee_id_attempted');
        });
    }

    public function down(): void
    {
        Schema::table('login_logs', function (Blueprint $table) {
            $table->renameColumn('employee_id_attempted', 'username_attempted');
            $table->renameIndex('idx_login_employee_id_attempted', 'idx_login_username_attempted');
        });
        DB::statement('ALTER TABLE users DROP CONSTRAINT chk_users_employee_id');
        Schema::table('users', fn (Blueprint $table) => $table->renameColumn('employee_id', 'username'));
        DB::statement('ALTER TABLE users RENAME CONSTRAINT uq_users_employee_id TO uq_users_username');
        DB::statement('CREATE UNIQUE INDEX uq_users_username_lower ON users (lower(username))');
        DB::statement('ALTER TABLE users ADD CONSTRAINT chk_users_username CHECK (length(trim(username)) >= 3)');
    }
};

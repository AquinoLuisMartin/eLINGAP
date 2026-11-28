<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->softDeletesTz();
            $table->dropUnique(['name']);
            $table->dropUnique(['program_id']);
        });

        DB::statement('CREATE UNIQUE INDEX sms_templates_live_name_unique ON sms_templates (name) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX sms_templates_live_program_unique ON sms_templates (program_id) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->unique('name');
            $table->unique('program_id');
            $table->dropIndex('sms_templates_live_name_unique');
            $table->dropIndex('sms_templates_live_program_unique');
            $table->dropSoftDeletesTz();
        });
    }
};

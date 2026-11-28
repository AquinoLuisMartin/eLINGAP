<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->smallInteger('birthday_year')->nullable();
            $table->unique(['senior_citizen_id', 'birthday_year'], 'sms_messages_senior_birthday_unique');
        });

        DB::table('sms_templates')->insertOrIgnore([
            'name' => 'Birthday Greeting',
            'body' => 'Maligayang Kaarawan po, [Senior Name]! Mula sa OSCA Santa Maria, binabati po namin kayo ng malusog at masayang kaarawan.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropUnique('sms_messages_senior_birthday_unique');
            $table->dropColumn('birthday_year');
        });
    }
};

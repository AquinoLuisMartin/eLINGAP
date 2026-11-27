<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['senior_citizens', 'users'] as $table) {
            DB::statement("UPDATE {$table}
                SET name_suffix = CASE upper(btrim(name_suffix))
                    WHEN '' THEN NULL
                    WHEN 'JR' THEN 'Jr.'
                    WHEN 'JR.' THEN 'Jr.'
                    WHEN 'SR' THEN 'Sr.'
                    WHEN 'SR.' THEN 'Sr.'
                    ELSE upper(btrim(name_suffix))
                END
                WHERE name_suffix NOT IN ('Jr.', 'Sr.', 'II', 'III', 'IV', 'V')");

            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_name_suffix_check
                CHECK (name_suffix IN ('Jr.', 'Sr.', 'II', 'III', 'IV', 'V'))");
        }
    }

    public function down(): void
    {
        // Keep canonical values when removing the constraints.
        foreach (['senior_citizens', 'users'] as $table) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$table}_name_suffix_check");
        }
    }
};

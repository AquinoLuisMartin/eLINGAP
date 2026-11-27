<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $pattern = '\mSanta[[:blank:]]+(?=[[:alpha:]])';

        DB::update("UPDATE barangays SET name = regexp_replace(name, ?, 'Sta. ', 'gi') WHERE name ~* ?", [$pattern, $pattern]);
        DB::update("UPDATE programs SET agency = regexp_replace(agency, ?, 'Sta. ', 'gi') WHERE agency ~* ?", [$pattern, $pattern]);
        DB::update(
            "UPDATE system_configurations SET value = regexp_replace(value, ?, 'Sta. ', 'gi')
             WHERE key IN ('municipality', 'organization_name', 'office_name', 'office_address') AND value ~* ?",
            [$pattern, $pattern],
        );
    }

    public function down(): void
    {
        // Existing abbreviations and renamed values cannot be distinguished after the update.
    }
};

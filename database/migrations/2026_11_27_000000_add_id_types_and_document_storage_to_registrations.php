<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('id_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('issuing_agency', 150)->nullable();
            $table->string('category', 50);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order');
            $table->timestampsTz();
        });

        $types = [
            ['national_id', 'National ID (PhilSys)', 'Philippine Statistics Authority', 'national'],
            ['digital_national_id', 'Digital National ID', 'Philippine Statistics Authority', 'national'],
            ['ephilid', 'ePhilID', 'Philippine Statistics Authority', 'national'],
            ['passport', 'Philippine Passport', 'Department of Foreign Affairs', 'national'],
            ['drivers_license', 'Driver’s License', 'Land Transportation Office', 'license'],
            ['sss_id', 'SSS ID', 'Social Security System', 'membership'],
            ['gsis_id', 'GSIS ID', 'Government Service Insurance System', 'membership'],
            ['umid', 'UMID', 'SSS or GSIS', 'membership'],
            ['philhealth_id', 'PhilHealth ID', 'Philippine Health Insurance Corporation', 'membership'],
            ['pagibig_loyalty_card', 'Pag-IBIG Loyalty Card', 'Pag-IBIG Fund', 'membership'],
            ['postal_id', 'Postal ID', 'Philippine Postal Corporation', 'national'],
            ['voters_id', 'Voter’s ID', 'Commission on Elections', 'national'],
            ['tin_id', 'TIN ID', 'Bureau of Internal Revenue', 'national'],
            ['prc_id', 'PRC ID', 'Professional Regulation Commission', 'license'],
            ['pwd_id', 'PWD ID', 'Local government unit', 'local'],
            ['barangay_id', 'Barangay ID', 'Barangay government', 'local'],
            ['government_employee_id', 'Government Employee ID', 'Employing government agency', 'employment'],
            ['other_government_id', 'Other Government-Issued ID', null, 'other'],
            ['no_valid_id', 'No Valid ID Available', null, 'none'],
        ];

        $now = now();
        DB::table('id_types')->insert(array_map(fn (array $type, int $index) => [
            'code' => $type[0], 'name' => $type[1], 'issuing_agency' => $type[2], 'category' => $type[3],
            'sort_order' => $index + 1, 'created_at' => $now, 'updated_at' => $now,
        ], $types, array_keys($types)));

        Schema::table('senior_citizen_documents', function (Blueprint $table) {
            $table->unsignedInteger('id_type_id')->nullable();
            $table->foreign('id_type_id')->references('id')->on('id_types')->restrictOnDelete();
            $table->string('storage_disk', 20)->default('local');
            $table->unsignedInteger('stored_size')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('senior_citizen_documents', function (Blueprint $table) {
            $table->dropForeign(['id_type_id']);
            $table->dropColumn(['id_type_id', 'storage_disk', 'stored_size']);
        });

        Schema::dropIfExists('id_types');
    }
};

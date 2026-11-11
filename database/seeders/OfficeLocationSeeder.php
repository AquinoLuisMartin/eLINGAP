<?php

namespace Database\Seeders;

use App\Models\SystemConfiguration;
use Illuminate\Database\Seeder;

class OfficeLocationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['municipality' => 'Santa Maria', 'province' => 'Bulacan'] as $key => $value) {
            $setting = SystemConfiguration::firstOrNew(['key' => $key]);
            if (blank($setting->value)) {
                $setting->forceFill(['value' => $value, 'type' => 'string'])->save();
            }
        }
    }
}

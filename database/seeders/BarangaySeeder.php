<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Seeder;

class BarangaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barangays = [
            'BAG' => 'Bagbaguin',
            'BAL' => 'Balasing',
            'BUE' => 'Buenavista',
            'BUL' => 'Bulac',
            'CAM' => 'Camangyanan',
            'CAT' => 'Catmon',
            'CAY' => 'Cay Pombo (Caypombo)',
            'CYS' => 'Caysio',
            'GUY' => 'Guyong',
            'LAL' => 'Lalakhan',
            'MAS' => 'Mag-asawang Sapa',
            'MHP' => 'Mahabang Parang',
            'MAN' => 'Manggahan',
            'PAR' => 'Parada',
            'POB' => 'Poblacion',
            'PBU' => 'Pulong Buhangin',
            'SGA' => 'San Gabriel',
            'SJP' => 'San Jose Patag',
            'SVI' => 'San Vicente',
            'SCL' => 'Santa Clara',
            'SCR' => 'Santa Cruz',
            'SIL' => 'Silangan',
            'TBA' => 'Tabing Bakod (Santo Tomas)',
            'TUM' => 'Tumana',
        ];

        foreach ($barangays as $code => $name) {
            $barangay = Barangay::firstOrCreate(['name' => $name], ['code' => $code, 'is_active' => true]);

            if (! $barangay->is_active) {
                $barangay->update(['is_active' => true]);
            }
        }
    }
}

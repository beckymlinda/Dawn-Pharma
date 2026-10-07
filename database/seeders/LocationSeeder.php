<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Main Store',
                'address' => '123 Main Street, City Center',
                'city' => 'Lilongwe',
                'phone' => '+265 123 456 789',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Branch Store',
                'address' => '456 Market Avenue, District',
                'city' => 'Blantyre',
                'phone' => '+265 987 654 321',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($locations as $location) {
            Location::firstOrCreate(['name' => $location['name']], $location);
        }

        $this->command->info('Created ' . count($locations) . ' locations successfully!');
    }
}

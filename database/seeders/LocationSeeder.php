<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        Location::create([
            'name' => 'Main Warehouse',
            'code' => 'WH-A',
            'address' => '123 Industrial Blvd, District 7',
            'type' => 'warehouse',
            'status' => 'active',
        ]);

        Location::create([
            'name' => 'North Distribution Center',
            'code' => 'WH-B',
            'address' => '456 Logistics Pkwy, North Zone',
            'type' => 'warehouse',
            'status' => 'active',
        ]);

        Location::create([
            'name' => 'Downtown Store',
            'code' => 'ST-01',
            'address' => '789 Main Street, Downtown',
            'type' => 'store',
            'status' => 'active',
        ]);

        Location::create([
            'name' => 'Mall Kiosk',
            'code' => 'ST-02',
            'address' => 'Shopping Mall, Level 2, Unit 45',
            'type' => 'store',
            'status' => 'active',
        ]);

        Location::create([
            'name' => 'East End Store',
            'code' => 'ST-03',
            'address' => '321 East Ave, Eastern District',
            'type' => 'store',
            'status' => 'inactive',
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::create([
            'name' => 'Global Foods Inc.',
            'contact_person' => 'Maria Chen',
            'phone' => '+1-555-0101',
            'email' => 'mchen@globalfoods.example.com',
            'status' => 'active',
        ]);

        Supplier::create([
            'name' => 'TechParts Distributors',
            'contact_person' => 'James Wilson',
            'phone' => '+1-555-0102',
            'email' => 'jwilson@techparts.example.com',
            'status' => 'active',
        ]);

        Supplier::create([
            'name' => 'Beverage Co. Ltd',
            'contact_person' => 'Lisa Park',
            'phone' => '+1-555-0103',
            'email' => 'lpark@beverageco.example.com',
            'status' => 'active',
        ]);

        Supplier::create([
            'name' => 'Snacks Unlimited',
            'contact_person' => null,
            'phone' => '+1-555-0104',
            'email' => 'orders@snacksunlimited.example.com',
            'status' => 'active',
        ]);

        Supplier::create([
            'name' => 'Discontinued Supplier Co',
            'contact_person' => 'Old Contact',
            'phone' => '+1-555-0199',
            'email' => 'info@old-supplier.example.com',
            'status' => 'inactive',
        ]);
    }
}

<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseTableSeeder extends Seeder {
    public function run(): void {
        DB::table('warehouses')->insert([
            'company_id'  => 1,
            'name'        => 'Main Warehouse',
            'slug'        => 'main-warehouse',
            'is_default'  => 1,
            'barcode_type'=> 'code128',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}

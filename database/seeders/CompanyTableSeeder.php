<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyTableSeeder extends Seeder {
    public function run(): void {
        DB::table('companies')->insert([
            'name'         => 'Default Company',
            'email'        => 'admin@company.com',
            'phone'        => '+1-000-000-0000',
            'country'      => 'US',
            'currency_code'=> 'USD',
            'status'       => 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
}

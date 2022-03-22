<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder {
    public function run(): void {
        $roles = ['admin' => 'Administrator', 'staff' => 'Staff Member', 'customer' => 'Customer'];
        foreach ($roles as $name => $display) {
            DB::table('roles')->insert([
                'company_id'   => 1,
                'name'         => $name,
                'display_name' => $display,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}

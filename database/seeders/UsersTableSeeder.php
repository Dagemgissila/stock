<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder {
    public function run(): void {
        DB::table('users')->insert([
            'company_id' => 1,
            'name'       => 'Admin',
            'email'      => 'admin@example.com',
            'password'   => Hash::make('admin123'),
            'status'     => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

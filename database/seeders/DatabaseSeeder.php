<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CompanyTableSeeder::class,
            CurrencyTableSeeder::class,
            LangTableSeeder::class,
            WarehouseTableSeeder::class,
            UsersTableSeeder::class,
            RolesTableSeeder::class,
            PermissionTableSeeder::class,
            PaymentModesTableSeeder::class,
            UnitTableSeeder::class,
            BrandsTableSeeder::class,
            CategoryTableSeeder::class,
        ]);
    }
}

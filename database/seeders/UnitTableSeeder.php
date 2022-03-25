<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitTableSeeder extends Seeder {
    public function run(): void {
        $units = [['Piece','pcs'],['Kilogram','kg'],['Litre','ltr'],
                  ['Meter','mtr'],['Box','box'],['Dozen','dz']];
        foreach ($units as [$name, $short]) {
            DB::table('units')->insert(['company_id'=>1,'name'=>$name,
                'short_name'=>$short,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
}

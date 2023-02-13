<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class BrandsTableSeeder extends Seeder {
    public function run(): void {
        foreach (['Generic','Samsung','Apple','Sony','Dell','HP','Lenovo'] as $brand) {
            DB::table('brands')->insert(['company_id'=>1,'name'=>$brand,
                'slug'=>\Str::slug($brand),'created_at'=>now(),'updated_at'=>now()]);
        }
    }
}

<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class CategoryTableSeeder extends Seeder {
    public function run(): void {
        foreach (['Electronics','Clothing','Food & Beverages','Hardware','Stationery','Other'] as $cat) {
            DB::table('categories')->insert(['company_id'=>1,'name'=>$cat,
                'slug'=>\Str::slug($cat),'created_at'=>now(),'updated_at'=>now()]);
        }
    }
}

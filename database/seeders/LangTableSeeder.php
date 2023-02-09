<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class LangTableSeeder extends Seeder {
    public function run(): void {
        DB::table('langs')->insert([
            ['name'=>'English','key'=>'en','flag'=>'us','is_rtl'=>false,'status'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Arabic',  'key'=>'ar','flag'=>'sa','is_rtl'=>true, 'status'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'French',  'key'=>'fr','flag'=>'fr','is_rtl'=>false,'status'=>true,'created_at'=>now(),'updated_at'=>now()],
        ]);
    }
}

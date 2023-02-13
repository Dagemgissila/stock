<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionTableSeeder extends Seeder {
    public function run(): void {
        $modules = ['products','categories','brands','taxes','units',
                    'sales','purchases','quotations','customers','suppliers',
                    'expenses','payments','stock-adjustments','stock-transfers',
                    'warehouses','users','roles','reports','settings'];
        foreach ($modules as $module) {
            foreach (['view','create','edit','delete'] as $action) {
                DB::table('permissions')->updateOrInsert(
                    ['name' => $module.'-'.$action],
                    ['display_name'=>ucfirst($action).' '.ucfirst($module),
                     'module'=>$module,'created_at'=>now(),'updated_at'=>now()]
                );
            }
        }
    }
}

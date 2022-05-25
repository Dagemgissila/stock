<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentModesTableSeeder extends Seeder {
    public function run(): void {
        $modes = [
            ['Cash', true], ['Card', false],
            ['Bank Transfer', false], ['Cheque', false],
        ];
        foreach ($modes as [$name, $default]) {
            DB::table('payment_modes')->insert(['company_id'=>1,'name'=>$name,
                'is_default'=>$default,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
}

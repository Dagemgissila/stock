<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencyTableSeeder extends Seeder {
    public function run(): void {
        $currencies = [
            ['USD','$','prefix',2,true],
            ['EUR','€','prefix',2,false],
            ['GBP','£','prefix',2,false],
            ['INR','₹','prefix',2,false],
            ['JPY','¥','prefix',0,false],
            ['AED','AED','prefix',2,false],
            ['SAR','SAR','prefix',2,false],
            ['NGN','₦','prefix',2,false],
            ['BDT','৳','prefix',2,false],
            ['PKR','₨','prefix',2,false],
        ];
        foreach ($currencies as [$code, $symbol, $pos, $dec, $def]) {
            DB::table('currencies')->insert(['company_id'=>1,'name'=>$code,
                'code'=>$code,'symbol'=>$symbol,'position'=>$pos,
                'decimal_places'=>$dec,'is_default'=>$def,
                'created_at'=>now(),'updated_at'=>now()]);
        }
    }
}

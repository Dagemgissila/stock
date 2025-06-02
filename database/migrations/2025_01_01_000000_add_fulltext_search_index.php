<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration{
    public function up():void{
        try{
            DB::statement('ALTER TABLE products ADD FULLTEXT INDEX ft_products_name_barcode (name, barcode)');
        }catch(\Exception $e){
            // FULLTEXT requires InnoDB/MyISAM, skip if not supported
        }
    }
    public function down():void{
        try{ DB::statement('ALTER TABLE products DROP INDEX ft_products_name_barcode'); }catch(\Exception $e){}
    }
};

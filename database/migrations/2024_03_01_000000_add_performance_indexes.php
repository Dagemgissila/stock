<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('orders'))
            Schema::table('orders', fn($t) => $t->index(['company_id','order_type','order_date'],'idx_orders_ctd'));
        if (Schema::hasTable('order_items'))
            Schema::table('order_items', fn($t) => $t->index(['order_id','product_id'],'idx_oi_op'));
        if (Schema::hasTable('warehouse_stocks'))
            Schema::table('warehouse_stocks', fn($t) => $t->index(['company_id','warehouse_id'],'idx_ws_cw'));
        if (Schema::hasTable('stock_history'))
            Schema::table('stock_history', fn($t) => $t->index(['product_id','created_at'],'idx_sh_pc'));
    }
    public function down(): void {}
};

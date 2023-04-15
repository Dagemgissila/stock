<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Orders: composite index for report date range queries
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index(['company_id','order_type','order_date'], 'idx_orders_company_type_date');
            });
        }
        // Order items: join performance
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->index(['order_id','product_id'], 'idx_order_items_order_product');
            });
        }
        // Warehouse stocks: primary lookup path
        if (Schema::hasTable('warehouse_stocks')) {
            Schema::table('warehouse_stocks', function (Blueprint $table) {
                $table->index(['company_id','warehouse_id'], 'idx_wstock_company_warehouse');
            });
        }
        // Stock history: time-series queries
        if (Schema::hasTable('stock_history')) {
            Schema::table('stock_history', function (Blueprint $table) {
                $table->index(['product_id','created_at'], 'idx_stock_history_product_date');
            });
        }
    }
    public function down(): void {
        Schema::table('orders',          fn($t)=>$t->dropIndex('idx_orders_company_type_date'));
        Schema::table('order_items',     fn($t)=>$t->dropIndex('idx_order_items_order_product'));
        Schema::table('warehouse_stocks',fn($t)=>$t->dropIndex('idx_wstock_company_warehouse'));
        Schema::table('stock_history',   fn($t)=>$t->dropIndex('idx_stock_history_product_date'));
    }
};

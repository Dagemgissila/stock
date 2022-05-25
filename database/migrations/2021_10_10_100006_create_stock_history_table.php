<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 12, 3);
            $table->string('order_type')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('type')->default('in'); // in | out
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['product_id','created_at']);
            $table->index(['company_id','warehouse_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('stock_history'); }
};

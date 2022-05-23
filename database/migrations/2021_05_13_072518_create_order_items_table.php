<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('quantity',   12, 3)->default(0);
            $table->decimal('discount',   12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('subtotal',   12, 2)->default(0);
            $table->decimal('mrp',        12, 2)->nullable();
            $table->timestamps();
            $table->index(['order_id','product_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('order_items'); }
};

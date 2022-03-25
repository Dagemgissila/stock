<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('tax_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('barcode')->nullable()->index();
            $table->string('image')->nullable();
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('sales_price',    12, 2)->default(0);
            $table->decimal('mrp',            12, 2)->nullable();
            $table->integer('stock_alert')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};

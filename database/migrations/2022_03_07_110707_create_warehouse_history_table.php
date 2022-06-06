<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('warehouse_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('from_warehouse_id')->nullable();
            $table->unsignedBigInteger('to_warehouse_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity',12,3);
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('staff_user_id')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('warehouse_history'); }
};

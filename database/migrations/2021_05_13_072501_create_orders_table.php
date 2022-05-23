<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('from_warehouse_id')->nullable();
            $table->string('order_type');   // purchase | sales | quotation | stock_transfer
            $table->string('invoice_number')->unique();
            $table->string('order_status')->default('pending');
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('party_type')->nullable();
            $table->decimal('subtotal',    12, 2)->default(0);
            $table->decimal('discount',    12, 2)->default(0);
            $table->decimal('tax_amount',  12, 2)->default(0);
            $table->decimal('shipping',    12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('due_amount',  12, 2)->default(0);
            $table->date('order_date');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('staff_user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['company_id','order_type','order_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('orders'); }
};

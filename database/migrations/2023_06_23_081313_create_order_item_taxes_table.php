<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('order_item_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('order_item_id');
            $table->unsignedBigInteger('tax_id');
            $table->string('tax_name');
            $table->decimal('tax_rate',8,2);
            $table->decimal('tax_amount',12,2);
            $table->timestamps();
            $table->index(['order_item_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('order_item_taxes'); }
};

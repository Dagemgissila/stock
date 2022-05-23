<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('payment_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
            $table->index(['order_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('order_payments'); }
};

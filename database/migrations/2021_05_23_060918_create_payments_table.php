<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('payment_mode_id')->nullable();
            $table->unsignedBigInteger('staff_user_id')->nullable();
            $table->string('payable_type')->nullable();
            $table->unsignedBigInteger('payable_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->dateTime('date');
            $table->string('payment_type')->default('in'); // in | out
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['payable_type','payable_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('payments'); }
};

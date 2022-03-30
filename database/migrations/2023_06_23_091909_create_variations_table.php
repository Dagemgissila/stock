<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('variations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('name');
            $table->string('type')->default('text');
            $table->timestamps();
        });
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variation_id');
            $table->string('name');
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('quantity', 12, 3)->default(0);
            $table->timestamps();
            $table->index(['product_id','variation_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('variations');
    }
};

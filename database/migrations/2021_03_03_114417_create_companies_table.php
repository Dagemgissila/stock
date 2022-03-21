<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('logo')->nullable();
            $table->string('login_image')->nullable();
            $table->string('country')->nullable();
            $table->string('currency_code', 10)->default('USD');
            $table->boolean('status')->default(true);
            $table->boolean('is_rtl')->default(false);
            $table->boolean('white_label_complete')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('companies'); }
};

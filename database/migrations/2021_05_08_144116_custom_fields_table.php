<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('name');
            $table->enum('type',['text','number','date','dropdown']);
            $table->json('values')->nullable();
            $table->boolean('is_required')->default(false);
            $table->string('default_value')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('custom_fields'); }
};

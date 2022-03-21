<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lang_id');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->foreign('lang_id')->references('id')->on('langs')->onDelete('cascade');
            $table->index(['lang_id', 'key']);
        });
    }
    public function down(): void { Schema::dropIfExists('translations'); }
};

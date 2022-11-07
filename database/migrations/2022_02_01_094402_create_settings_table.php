<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('setting_type')->default('general');
            $table->string('name');
            $table->string('type')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
            $table->unique(['company_id','name']);
        });
    }
    public function down(): void { Schema::dropIfExists('settings'); }
};

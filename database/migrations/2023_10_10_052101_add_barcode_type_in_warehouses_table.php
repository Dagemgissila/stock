<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses','barcode_type'))
                $table->string('barcode_type')->default('code128')->after('is_rtl');
        });
    }
    public function down(): void { Schema::table('warehouses',fn($t)=>$t->dropColumn('barcode_type')); }
};

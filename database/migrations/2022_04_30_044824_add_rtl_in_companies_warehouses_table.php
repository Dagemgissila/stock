<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies','is_rtl'))
                $table->boolean('is_rtl')->default(false)->after('status');
        });
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses','is_rtl'))
                $table->boolean('is_rtl')->default(false)->after('is_default');
        });
    }
    public function down(): void {
        Schema::table('companies',  fn($t)=>$t->dropColumn('is_rtl'));
        Schema::table('warehouses', fn($t)=>$t->dropColumn('is_rtl'));
    }
};

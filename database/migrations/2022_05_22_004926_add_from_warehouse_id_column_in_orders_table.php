<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders','from_warehouse_id'))
                $table->unsignedBigInteger('from_warehouse_id')->nullable()->after('warehouse_id');
        });
    }
    public function down(): void { Schema::table('orders',fn($t)=>$t->dropColumn('from_warehouse_id')); }
};

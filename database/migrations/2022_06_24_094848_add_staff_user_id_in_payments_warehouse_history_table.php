<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments','staff_user_id'))
                $table->unsignedBigInteger('staff_user_id')->nullable()->after('payment_mode_id');
        });
        Schema::table('warehouse_history', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouse_history','staff_user_id'))
                $table->unsignedBigInteger('staff_user_id')->nullable()->after('order_id');
        });
    }
    public function down(): void {}
};

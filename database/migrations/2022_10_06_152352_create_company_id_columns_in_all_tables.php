<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    private array $tables = [
        'brands','categories','taxes','units','expense_categories',
        'expenses','custom_fields','stock_adjustments',
    ];
    public function up(): void {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table,'company_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                });
            }
        }
    }
    public function down(): void {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table,'company_id'))
                Schema::table($table, fn($t)=>$t->dropColumn('company_id'));
        }
    }
};

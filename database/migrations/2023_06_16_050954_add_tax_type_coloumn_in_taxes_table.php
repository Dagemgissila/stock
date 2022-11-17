<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('taxes', function (Blueprint $table) {
            if (!Schema::hasColumn('taxes','tax_type'))
                $table->enum('tax_type',['inclusive','exclusive'])->default('exclusive')->after('rate');
        });
    }
    public function down(): void { Schema::table('taxes',fn($t)=>$t->dropColumn('tax_type')); }
};

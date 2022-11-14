<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users','tax_number'))
                $table->string('tax_number')->nullable()->after('phone');
        });
    }
    public function down(): void { Schema::table('users',fn($t)=>$t->dropColumn('tax_number')); }
};

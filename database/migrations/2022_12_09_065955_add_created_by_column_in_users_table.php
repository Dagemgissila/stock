<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users','created_by'))
                $table->unsignedBigInteger('created_by')->nullable()->after('company_id');
        });
    }
    public function down(): void { Schema::table('users',fn($t)=>$t->dropColumn('created_by')); }
};

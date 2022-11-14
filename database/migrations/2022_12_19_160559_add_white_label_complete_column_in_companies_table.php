<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies','white_label_complete'))
                $table->boolean('white_label_complete')->default(false)->after('is_rtl');
            if (!Schema::hasColumn('companies','login_image'))
                $table->string('login_image')->nullable()->after('logo');
        });
    }
    public function down(): void {
        Schema::table('companies',fn($t)=>$t->dropColumn(['white_label_complete','login_image']));
    }
};

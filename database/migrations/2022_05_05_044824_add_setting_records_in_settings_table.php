<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        $defaults = [
            ['general','invoice_footer',          'text',    false],
            ['general','allow_negative_stock',     'boolean', false],
            ['general','auto_send_invoice_email',  'boolean', false],
            ['general','pos_print_type',           'text',    false],
            ['general','dark_mode',                'boolean', false],
            ['general','require_2fa',              'boolean', false],
            ['general','stock_alert_notification', 'boolean', true],
        ];
        foreach ($defaults as [$type, $name, $valType, $status]) {
            DB::table('settings')->updateOrInsert(
                ['company_id' => 1, 'name' => $name],
                ['setting_type'=>$type,'type'=>$valType,'status'=>$status,
                 'created_at'=>now(),'updated_at'=>now()]
            );
        }
    }
    public function down(): void {}
};

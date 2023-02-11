<?php
namespace App\Observers;
use App\Models\PaymentMode;
class PaymentModeObserver {
    public function saving(PaymentMode $pm): void {
        if ($pm->is_default) {
            PaymentMode::where('company_id',$pm->company_id)->where('id','!=',$pm->id??0)->update(['is_default'=>false]);
        }
    }
}

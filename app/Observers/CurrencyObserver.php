<?php
namespace App\Observers;

use App\Models\Currency;

class CurrencyObserver
{
    public function saving(Currency $currency): void
    {
        // Enforce only one default currency per company
        if ($currency->is_default) {
            Currency::where('company_id', $currency->company_id)
                ->where('id', '!=', $currency->id ?? 0)
                ->update(['is_default' => false]);
        }
    }
}

<?php
namespace App\Observers;

use App\Models\Variation;

class VariationObserver
{
    public function deleting(Variation $variation): void
    {
        $variation->productVariants()->delete();
    }
}

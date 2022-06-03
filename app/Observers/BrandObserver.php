<?php
namespace App\Observers;
use App\Models\Brand;
class BrandObserver {
    public function saved(Brand $b): void   { \Cache::forget('brands_'.$b->company_id); }
    public function deleted(Brand $b): void { \Cache::forget('brands_'.$b->company_id); }
}

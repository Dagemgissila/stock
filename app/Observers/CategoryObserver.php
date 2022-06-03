<?php
namespace App\Observers;
use App\Models\Category;
class CategoryObserver {
    public function saved(Category $c): void   { \Cache::forget('categories_'.$c->company_id); }
    public function deleted(Category $c): void { \Cache::forget('categories_'.$c->company_id); }
}

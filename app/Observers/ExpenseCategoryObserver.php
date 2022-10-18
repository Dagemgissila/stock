<?php
namespace App\Observers;
use App\Models\ExpenseCategory;
class ExpenseCategoryObserver {
    public function saved(ExpenseCategory $ec): void { \Cache::forget('expense_cats_'.$ec->company_id); }
}

<?php
namespace App\Observers;

use App\Models\Expense;

class ExpenseObserver
{
    public function created(Expense $expense): void
    {
        \Log::info('Expense created: ' . $expense->amount .
            ' in category ' . $expense->expense_category_id);
    }
    public function deleted(Expense $expense): void
    {
        \Log::info('Expense deleted: id=' . $expense->id);
    }
}

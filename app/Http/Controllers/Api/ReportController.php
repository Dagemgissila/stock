<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Product;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReportController extends ApiBaseController
{
    public function salesSummary(Request $request): JsonResponse
    {
        $query = Order::ofType('sales')
            ->withDateRange($request->from_date, $request->to_date);

        $summary = $query->selectRaw(
            'COUNT(*) as total_orders,
             SUM(grand_total) as total_revenue,
             SUM(due_amount)  as total_due,
             SUM(grand_total - due_amount) as total_collected'
        )->first();

        return $this->sendResponse($summary, 'Sales summary');
    }

    public function purchaseSummary(Request $request): JsonResponse
    {
        $query = Order::ofType('purchase')
            ->withDateRange($request->from_date, $request->to_date);

        $summary = $query->selectRaw(
            'COUNT(*) as total_orders,
             SUM(grand_total) as total_purchase,
             SUM(due_amount)  as total_due'
        )->first();

        return $this->sendResponse($summary, 'Purchase summary');
    }

    public function profitLoss(Request $request): JsonResponse
    {
        $revenue  = Order::ofType('sales')
            ->withDateRange($request->from_date, $request->to_date)
            ->sum('grand_total');

        $cogs     = Order::ofType('purchase')
            ->withDateRange($request->from_date, $request->to_date)
            ->sum('grand_total');

        $expenses = Expense::withDateRange($request->from_date, $request->to_date)
            ->sum('amount') ?? 0;

        return $this->sendResponse([
            'revenue'      => $revenue,
            'cost_of_goods'=> $cogs,
            'expenses'     => $expenses,
            'gross_profit' => $revenue - $cogs,
            'net_profit'   => $revenue - $cogs - $expenses,
        ], 'Profit & Loss report');
    }

    public function stockReport(Request $request): JsonResponse
    {
        $query = WarehouseStock::with(['product.category','product.brand','warehouse']);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->low_stock)    $query->whereColumn('quantity','<=',
            DB::raw('(SELECT stock_alert FROM products WHERE products.id = warehouse_stocks.product_id)'));
        return $this->sendResponse($this->paginate($query));
    }

    public function paymentReport(Request $request): JsonResponse
    {
        $query = Payment::with('paymentMode')
            ->withDateRange($request->from_date, $request->to_date);
        $summary = [
            'total_in'  => (clone $query)->where('payment_type','in')->sum('amount'),
            'total_out' => (clone $query)->where('payment_type','out')->sum('amount'),
            'payments'  => $this->paginate($query->latest()),
        ];
        return $this->sendResponse($summary, 'Payment report');
    }

    public function expenseReport(Request $request): JsonResponse
    {
        $query = Expense::with('category')
            ->withDateRange($request->from_date, $request->to_date);
        $byCategory = Expense::withDateRange($request->from_date, $request->to_date)
            ->select('expense_category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('expense_category_id')
            ->with('category')
            ->get();
        return $this->sendResponse([
            'total_expenses' => $query->sum('amount'),
            'by_category'    => $byCategory,
            'expenses'       => $this->paginate($query->latest()),
        ], 'Expense report');
    }

    public function dashboard(Request $request): JsonResponse
    {
        $cacheKey = 'dashboard_' . auth('api')->user()->company_id;
        return $this->sendResponse(
            cache()->remember($cacheKey, 300, function () {
                return [
                    'total_sales'     => Order::ofType('sales')->sum('grand_total'),
                    'total_purchases' => Order::ofType('purchase')->sum('grand_total'),
                    'total_expenses'  => Expense::sum('amount'),
                    'total_customers' => \App\Models\Customer::count(),
                    'total_suppliers' => \App\Models\Supplier::count(),
                    'total_products'  => Product::count(),
                    'low_stock_count' => WarehouseStock::whereColumn(
                        'quantity','<=',
                        DB::raw('(SELECT stock_alert FROM products WHERE products.id = warehouse_stocks.product_id)')
                    )->count(),
                ];
            }),
            'Dashboard summary'
        );
    }
}

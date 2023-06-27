<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReportController extends ApiBaseController
{
    public function dashboard(): JsonResponse
    {
        $companyId = auth('api')->user()->company_id;
        $cacheKey  = 'dashboard_' . $companyId;

        $data = Cache::remember($cacheKey, 300, function () use ($companyId) {
            return [
                'total_sales'     => Order::ofType('sales')->sum('grand_total'),
                'total_purchases' => Order::ofType('purchase')->sum('grand_total'),
                'total_expenses'  => Expense::sum('amount'),
                'total_customers' => Customer::count(),
                'total_suppliers' => Supplier::count(),
                'total_products'  => Product::where('status',1)->count(),
                'low_stock_count' => WarehouseStock::whereColumn(
                    'quantity','<=',
                    DB::raw('(SELECT stock_alert FROM products WHERE products.id=warehouse_stocks.product_id AND products.deleted_at IS NULL)')
                )->count(),
            ];
        });

        return $this->sendResponse($data, 'Dashboard data (cached 5 min)');
    }

    public function salesSummary(Request $request): JsonResponse
    {
        $query = Order::ofType('sales')->withDateRange($request->from_date, $request->to_date);
        return $this->sendResponse($query->selectRaw(
            'COUNT(*) as total_orders, SUM(grand_total) as total_revenue,
             SUM(due_amount) as total_due, SUM(grand_total-due_amount) as total_collected'
        )->first(), 'Sales summary');
    }

    public function purchaseSummary(Request $request): JsonResponse
    {
        $query = Order::ofType('purchase')->withDateRange($request->from_date,$request->to_date);
        return $this->sendResponse($query->selectRaw(
            'COUNT(*) as total_orders, SUM(grand_total) as total_purchase, SUM(due_amount) as total_due'
        )->first(),'Purchase summary');
    }

    public function profitLoss(Request $request): JsonResponse
    {
        $revenue  = Order::ofType('sales')->withDateRange($request->from_date,$request->to_date)->sum('grand_total');
        $cogs     = Order::ofType('purchase')->withDateRange($request->from_date,$request->to_date)->sum('grand_total');
        $expenses = Expense::withDateRange($request->from_date,$request->to_date)->sum('amount');
        return $this->sendResponse(['revenue'=>$revenue,'cogs'=>$cogs,'expenses'=>$expenses,
            'gross_profit'=>$revenue-$cogs,'net_profit'=>$revenue-$cogs-$expenses],'P&L');
    }

    public function stockReport(Request $request): JsonResponse
    {
        // Eager load to eliminate N+1 on product.category and product.brand
        $query = WarehouseStock::with(['product.category','product.brand','warehouse']);
        if ($request->warehouse_id) $query->where('warehouse_id',$request->warehouse_id);
        if ($request->low_stock)    $query->whereColumn('quantity','<=',
            DB::raw('(SELECT stock_alert FROM products WHERE products.id=warehouse_stocks.product_id)'));
        return $this->sendResponse($this->paginate($query));
    }

    public function paymentReport(Request $request): JsonResponse
    {
        $base   = Payment::withDateRange($request->from_date,$request->to_date);
        return $this->sendResponse([
            'total_in'  => (clone $base)->where('payment_type','in')->sum('amount'),
            'total_out' => (clone $base)->where('payment_type','out')->sum('amount'),
            'payments'  => $this->paginate((clone $base)->with('paymentMode')->latest()),
        ],'Payment report');
    }

    public function expenseReport(Request $request): JsonResponse
    {
        $query = Expense::with('category')->withDateRange($request->from_date,$request->to_date);
        return $this->sendResponse([
            'total'       => $query->sum('amount'),
            'by_category' => Expense::withDateRange($request->from_date,$request->to_date)
                ->select('expense_category_id',DB::raw('SUM(amount) as total'))
                ->groupBy('expense_category_id')->with('category')->get(),
            'expenses'    => $this->paginate($query->latest()),
        ],'Expense report');
    }
}

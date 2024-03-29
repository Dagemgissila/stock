<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\{Order,Payment,Expense,Product,WarehouseStock,Customer,Supplier};
use Illuminate\Http\{Request,JsonResponse};
use Illuminate\Support\Facades\{DB,Cache};

class ReportController extends ApiBaseController
{
    public function dashboard(): JsonResponse
    {
        $cid = auth('api')->user()->company_id;
        return $this->sendResponse(Cache::remember('dashboard_'.$cid, 300, fn() => [
            'total_sales'      => Order::ofType('sales')->sum('grand_total'),
            'total_purchases'  => Order::ofType('purchase')->sum('grand_total'),
            'total_expenses'   => Expense::sum('amount'),
            'total_customers'  => Customer::count(),
            'total_suppliers'  => Supplier::count(),
            'total_products'   => Product::where('status',1)->count(),
            'low_stock_count'  => WarehouseStock::whereColumn('quantity','<=',
                DB::raw('(SELECT stock_alert FROM products WHERE products.id=warehouse_stocks.product_id AND products.deleted_at IS NULL)'))->count(),
            'recent_sales'     => Order::ofType('sales')->latest()->limit(5)->with('party')->get(),
            'recent_purchases' => Order::ofType('purchase')->latest()->limit(5)->with('party')->get(),
        ]), 'Dashboard (cached 5 min)');
    }
    public function salesSummary(Request $r): JsonResponse {
        return $this->sendResponse(Order::ofType('sales')->withDateRange($r->from_date,$r->to_date)->selectRaw('COUNT(*) as total_orders,SUM(grand_total) as total_revenue,SUM(due_amount) as total_due,SUM(grand_total-due_amount) as total_collected')->first(),'Sales summary');
    }
    public function purchaseSummary(Request $r): JsonResponse {
        return $this->sendResponse(Order::ofType('purchase')->withDateRange($r->from_date,$r->to_date)->selectRaw('COUNT(*) as total_orders,SUM(grand_total) as total_purchase,SUM(due_amount) as total_due')->first(),'Purchase summary');
    }
    public function profitLoss(Request $r): JsonResponse {
        $rev=Order::ofType('sales')->withDateRange($r->from_date,$r->to_date)->sum('grand_total');
        $cogs=Order::ofType('purchase')->withDateRange($r->from_date,$r->to_date)->sum('grand_total');
        $exp=Expense::withDateRange($r->from_date,$r->to_date)->sum('amount');
        return $this->sendResponse(['revenue'=>$rev,'cogs'=>$cogs,'expenses'=>$exp,'gross_profit'=>$rev-$cogs,'net_profit'=>$rev-$cogs-$exp],'P&L');
    }
    public function stockReport(Request $r): JsonResponse {
        $q=WarehouseStock::with(['product.category','product.brand','warehouse']);
        if ($r->warehouse_id) $q->where('warehouse_id',$r->warehouse_id);
        if ($r->low_stock)    $q->whereColumn('quantity','<=',DB::raw('(SELECT stock_alert FROM products WHERE products.id=warehouse_stocks.product_id)'));
        return $this->sendResponse($this->paginate($q));
    }
    public function paymentReport(Request $r): JsonResponse {
        $base=Payment::withDateRange($r->from_date,$r->to_date);
        return $this->sendResponse(['total_in'=>(clone $base)->where('payment_type','in')->sum('amount'),'total_out'=>(clone $base)->where('payment_type','out')->sum('amount'),'payments'=>$this->paginate((clone $base)->with('paymentMode')->latest())],'Payments');
    }
    public function expenseReport(Request $r): JsonResponse {
        $q=Expense::with('category')->withDateRange($r->from_date,$r->to_date);
        return $this->sendResponse(['total'=>$q->sum('amount'),'by_category'=>Expense::withDateRange($r->from_date,$r->to_date)->select('expense_category_id',DB::raw('SUM(amount) as total'))->groupBy('expense_category_id')->with('category')->get(),'expenses'=>$this->paginate($q->latest())],'Expenses');
    }
}

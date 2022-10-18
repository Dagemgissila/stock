<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExpenseController extends ApiBaseController {
    public function index(Request $request): JsonResponse {
        $query = Expense::with(['category','user']);
        if ($request->expense_category_id) $query->where('expense_category_id',$request->expense_category_id);
        if ($request->from_date) $query->whereDate('date','>=',$request->from_date);
        if ($request->to_date)   $query->whereDate('date','<=',$request->to_date);
        return $this->sendResponse($this->paginate($query->latest()));
    }
    public function store(Request $request): JsonResponse {
        $data = $request->validate(['expense_category_id'=>'required|exists:expense_categories,id',
            'amount'=>'required|numeric|min:0.01','date'=>'required|date']);
        $data['company_id'] = auth('api')->user()->company_id;
        $data['user_id']    = auth('api')->id();
        return $this->sendResponse(Expense::create($data)->load('category'),'Expense added',201);
    }
    public function update(Request $request, int $id): JsonResponse {
        Expense::findOrFail($id)->update($request->only(['expense_category_id','amount','date','note']));
        return $this->sendResponse(Expense::find($id)->load('category'),'Updated');
    }
    public function destroy(int $id): JsonResponse {
        Expense::findOrFail($id)->delete(); return $this->sendResponse([],'Deleted');
    }
}

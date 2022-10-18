<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExpenseCategoryController extends ApiBaseController {
    public function index(): JsonResponse { return $this->sendResponse($this->paginate(ExpenseCategory::withCount('expenses'))); }
    public function store(Request $request): JsonResponse {
        $data = $request->validate(['name'=>'required|max:100']);
        $data['company_id'] = auth('api')->user()->company_id;
        return $this->sendResponse(ExpenseCategory::create($data),'Category created',201);
    }
    public function update(Request $request, int $id): JsonResponse {
        ExpenseCategory::findOrFail($id)->update($request->only(['name','description']));
        return $this->sendResponse(ExpenseCategory::find($id),'Updated');
    }
    public function destroy(int $id): JsonResponse {
        ExpenseCategory::findOrFail($id)->delete(); return $this->sendResponse([],'Deleted');
    }
}

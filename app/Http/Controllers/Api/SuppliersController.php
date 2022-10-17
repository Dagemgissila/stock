<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SuppliersController extends ApiBaseController {
    public function index(Request $request): JsonResponse {
        $query = Supplier::query();
        if ($request->search) $query->where('name','like','%'.$request->search.'%');
        return $this->sendResponse($this->paginate($query));
    }
    public function store(Request $request): JsonResponse {
        $data = $request->validate(['name'=>'required']);
        $data['company_id'] = auth('api')->user()->company_id;
        return $this->sendResponse(Supplier::create($data),'Supplier created',201);
    }
    public function update(Request $request, int $id): JsonResponse {
        Supplier::findOrFail($id)->update($request->only(['name','email','phone','address','tax_number','status']));
        return $this->sendResponse(Supplier::find($id),'Supplier updated');
    }
    public function destroy(int $id): JsonResponse {
        Supplier::findOrFail($id)->delete(); return $this->sendResponse([],'Deleted');
    }
    public function ledger(int $id): JsonResponse {
        return $this->sendResponse(Supplier::findOrFail($id)->getLedger());
    }
}

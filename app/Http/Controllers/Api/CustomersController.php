<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomersController extends ApiBaseController {
    public function index(Request $request): JsonResponse {
        $query = Customer::query();
        if ($request->search) $query->where('name','like','%'.$request->search.'%')
            ->orWhere('email','like','%'.$request->search.'%');
        return $this->sendResponse($this->paginate($query));
    }
    public function store(Request $request): JsonResponse {
        $data = $request->validate(['name'=>'required','email'=>'nullable|email|unique:customers']);
        $data['company_id'] = auth('api')->user()->company_id;
        return $this->sendResponse(Customer::create($data),'Customer created',201);
    }
    public function show(int $id): JsonResponse {
        return $this->sendResponse(Customer::with('addresses')->findOrFail($id));
    }
    public function update(Request $request, int $id): JsonResponse {
        Customer::findOrFail($id)->update($request->only(['name','email','phone','address','tax_number','status']));
        return $this->sendResponse(Customer::find($id),'Customer updated');
    }
    public function destroy(int $id): JsonResponse {
        Customer::findOrFail($id)->delete(); return $this->sendResponse([],'Deleted');
    }
    public function ledger(int $id): JsonResponse {
        return $this->sendResponse(Customer::findOrFail($id)->getLedger());
    }
}

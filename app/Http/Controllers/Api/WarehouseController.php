<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WarehouseController extends ApiBaseController
{
    public function index(): JsonResponse
    {
        return $this->sendResponse($this->paginate(Warehouse::withCount('stocks')));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'required|max:100',
            'email'        => 'nullable|email',
            'phone'        => 'nullable|string',
            'barcode_type' => 'nullable|in:code128,qr,ean13,upc',
        ]);
        $data['company_id'] = auth('api')->user()->company_id;
        $data['slug']       = \Str::slug($data['name'].'-'.time());
        return $this->sendResponse(Warehouse::create($data), 'Warehouse created', 201);
    }

    public function show(int $id): JsonResponse
    {
        return $this->sendResponse(Warehouse::with('users')->findOrFail($id));
    }

    public function stock(int $id): JsonResponse
    {
        $stocks = WarehouseStock::with(['product.category','product.brand'])
            ->where('warehouse_id', $id)->get();
        return $this->sendResponse($stocks);
    }

    public function assignStaff(Request $request, int $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->users()->sync($request->input('user_ids', []));
        return $this->sendResponse([], 'Staff assigned to warehouse');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update($request->only(['name','email','phone','address','barcode_type','is_default']));
        return $this->sendResponse($warehouse->fresh(), 'Warehouse updated');
    }

    public function destroy(int $id): JsonResponse
    {
        Warehouse::findOrFail($id)->delete();
        return $this->sendResponse([], 'Warehouse deleted');
    }
}

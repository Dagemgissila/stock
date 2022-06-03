<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BrandController extends ApiBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Brand::withCount('products');
        if ($request->search) $query->where('name','like','%'.$request->search.'%');
        return $this->sendResponse($this->paginate($query));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|max:100']);
        $data['slug']       = \Str::slug($data['name'].'-'.time());
        $data['company_id'] = auth('api')->user()->company_id;
        return $this->sendResponse(Brand::create($data), 'Brand created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);
        $brand->update($request->only(['name','image']));
        return $this->sendResponse($brand->fresh(), 'Brand updated');
    }

    public function destroy(int $id): JsonResponse
    {
        Brand::findOrFail($id)->delete();
        return $this->sendResponse([], 'Brand deleted');
    }
}

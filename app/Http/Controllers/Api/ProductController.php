<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends ApiBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category','brand','unit','tax']);
        if ($request->category_id) $query->where('category_id', $request->category_id);
        if ($request->brand_id)    $query->where('brand_id',    $request->brand_id);
        if ($request->search)      $query->where('name', 'like', '%'.$request->search.'%')
                                         ->orWhere('barcode', $request->search);
        if ($request->status !== null) $query->where('status', $request->status);
        return $this->sendResponse($this->paginate($query->latest()));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'required|max:200',
            'category_id'    => 'required|exists:categories,id',
            'unit_id'        => 'required|exists:units,id',
            'sales_price'    => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'stock_alert'    => 'nullable|integer|min:0',
        ]);
        $data['slug']       = \Str::slug($data['name'].'-'.time());
        $data['company_id'] = auth('api')->user()->company_id;

        $product = Product::create($data);
        return $this->sendResponse(
            $product->load(['category','brand','unit','tax']),
            'Product created successfully', 201
        );
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with([
            'category','brand','unit','tax','variants','warehouseStocks.warehouse'
        ])->findOrFail($id);
        return $this->sendResponse($product);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->update($request->only([
            'name','category_id','brand_id','unit_id','tax_id',
            'sales_price','purchase_price','mrp','stock_alert','status','barcode',
        ]));
        return $this->sendResponse($product->fresh()->load(['category','brand']), 'Product updated');
    }

    public function destroy(int $id): JsonResponse
    {
        Product::findOrFail($id)->delete();
        return $this->sendResponse([], 'Product deleted');
    }

    public function search(Request $request): JsonResponse
    {
        $term     = $request->input('q', '');
        $products = Product::with('warehouseStocks')
            ->where('name',    'like', '%'.$term.'%')
            ->orWhere('barcode', $term)
            ->limit(20)->get();
        return $this->sendResponse($products);
    }
}

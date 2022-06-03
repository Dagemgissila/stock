<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends ApiBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::withCount('products');
        if ($request->parent_id) $query->where('parent_id', $request->parent_id);
        else $query->whereNull('parent_id');
        return $this->sendResponse($this->paginate($query));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|max:100', 'parent_id' => 'nullable|exists:categories,id']);
        $data['slug']       = \Str::slug($data['name'].'-'.time());
        $data['company_id'] = auth('api')->user()->company_id;
        return $this->sendResponse(Category::create($data), 'Category created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $cat = Category::findOrFail($id);
        $cat->update($request->only(['name','parent_id','image']));
        return $this->sendResponse($cat->fresh(), 'Category updated');
    }

    public function destroy(int $id): JsonResponse
    {
        Category::findOrFail($id)->delete();
        return $this->sendResponse([], 'Category deleted');
    }

    public function tree(): JsonResponse
    {
        $cats = Category::with('children')->whereNull('parent_id')->get();
        return $this->sendResponse($cats);
    }
}

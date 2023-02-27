<?php
namespace App\Http\Requests\Api\Product;
use App\Http\Requests\Api\BaseRequest;

class StoreProductRequest extends BaseRequest {
    public function rules(): array {
        return [
            'name'           => 'required|max:200',
            'category_id'    => 'required|exists:categories,id',
            'unit_id'        => 'required|exists:units,id',
            'sales_price'    => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'tax_id'         => 'nullable|exists:taxes,id',
            'brand_id'       => 'nullable|exists:brands,id',
            'barcode'        => 'nullable|unique:products,barcode',
            'stock_alert'    => 'nullable|integer|min:0',
            'mrp'            => 'nullable|numeric|min:0',
        ];
    }
}

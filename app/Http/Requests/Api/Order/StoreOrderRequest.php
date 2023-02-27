<?php
namespace App\Http\Requests\Api\Order;
use App\Http\Requests\Api\BaseRequest;

class StoreOrderRequest extends BaseRequest {
    public function rules(): array {
        return [
            'warehouse_id'          => 'required|exists:warehouses,id',
            'order_date'            => 'required|date',
            'discount'              => 'nullable|numeric|min:0',
            'shipping'              => 'nullable|numeric|min:0',
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.quantity'      => 'required|numeric|min:0.001',
            'items.*.unit_price'    => 'required|numeric|min:0',
            'items.*.discount'      => 'nullable|numeric|min:0',
        ];
    }
}

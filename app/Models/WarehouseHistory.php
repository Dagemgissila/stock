<?php
namespace App\Models;
class WarehouseHistory extends BaseModel {
    protected $fillable = ['company_id','from_warehouse_id','to_warehouse_id','product_id','quantity','order_id','staff_user_id'];
    public function fromWarehouse() { return $this->belongsTo(Warehouse::class,'from_warehouse_id'); }
    public function toWarehouse()   { return $this->belongsTo(Warehouse::class,'to_warehouse_id'); }
    public function product()       { return $this->belongsTo(Product::class); }
}

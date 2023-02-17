<?php
namespace App\Imports;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Str;

class ProductImport implements ToModel, WithHeadingRow, WithValidation {
    public function __construct(private int $companyId) {}

    public function model(array $row): ?Product {
        if (empty($row['name'])) return null;
        return new Product([
            'company_id'     => $this->companyId,
            'name'           => $row['name'],
            'slug'           => Str::slug($row['name'].'-'.time().rand(1,999)),
            'barcode'        => $row['barcode'] ?? null,
            'category_id'    => $row['category_id'] ?? null,
            'brand_id'       => $row['brand_id'] ?? null,
            'unit_id'        => $row['unit_id'] ?? null,
            'purchase_price' => $row['purchase_price'] ?? 0,
            'sales_price'    => $row['sales_price'] ?? 0,
            'stock_alert'    => $row['stock_alert'] ?? 0,
            'status'         => 1,
        ]);
    }

    public function rules(): array {
        return ['*.name' => 'required', '*.sales_price' => 'required|numeric'];
    }
}

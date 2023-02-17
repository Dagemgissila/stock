<?php
namespace App\Imports;
use App\Models\Brand;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class BrandImport implements ToModel, WithHeadingRow {
    public function __construct(private int $companyId) {}
    public function model(array $row): ?Brand {
        if (empty($row['name'])) return null;
        return Brand::firstOrCreate(
            ['company_id'=>$this->companyId,'name'=>$row['name']],
            ['slug'=>Str::slug($row['name'].'-'.time())]
        );
    }
}

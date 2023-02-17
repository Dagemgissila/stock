<?php
namespace App\Imports;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class CategoryImport implements ToModel, WithHeadingRow {
    public function __construct(private int $companyId) {}
    public function model(array $row): ?Category {
        if (empty($row['name'])) return null;
        return Category::firstOrCreate(
            ['company_id'=>$this->companyId,'name'=>$row['name']],
            ['slug'=>Str::slug($row['name'].'-'.time()),'parent_id'=>$row['parent_id']??null]
        );
    }
}

<?php
namespace App\Imports;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PartyImport implements ToModel, WithHeadingRow {
    public function __construct(private string $modelClass, private int $companyId) {}
    public function model(array $row): mixed {
        if (empty($row['name'])) return null;
        return new $this->modelClass([
            'company_id' => $this->companyId,
            'name'       => $row['name'],
            'email'      => $row['email'] ?? null,
            'phone'      => $row['phone'] ?? null,
            'address'    => $row['address'] ?? null,
            'status'     => 1,
        ]);
    }
}

<?php
namespace App\Imports;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;

class UserImport implements ToModel, WithHeadingRow {
    public function __construct(private int $companyId) {}
    public function model(array $row): ?User {
        if (empty($row['email'])) return null;
        if (User::where('email',$row['email'])->exists()) return null;
        return new User([
            'company_id' => $this->companyId,
            'name'       => $row['name'],
            'email'      => $row['email'],
            'password'   => Hash::make($row['password'] ?? 'password123'),
            'phone'      => $row['phone'] ?? null,
            'status'     => 1,
        ]);
    }
}

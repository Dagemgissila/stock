<?php
namespace App\Imports;

use App\Models\Translation;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TranslationImport implements ToModel, WithHeadingRow
{
    public function __construct(private int $langId) {}

    public function model(array $row): ?Translation
    {
        if (empty($row['key'])) return null;

        return Translation::updateOrCreate(
            ['lang_id' => $this->langId, 'key' => $row['key']],
            ['value' => $row['value'] ?? '']
        );
    }
}

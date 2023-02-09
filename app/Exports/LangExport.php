<?php
namespace App\Exports;

use App\Models\Translation;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LangExport implements FromQuery, WithHeadings
{
    public function __construct(private int $langId) {}

    public function query()
    {
        return Translation::where('lang_id', $this->langId)->select('key','value');
    }

    public function headings(): array
    {
        return ['key', 'value'];
    }
}

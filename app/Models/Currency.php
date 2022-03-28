<?php
namespace App\Models;

class Currency extends BaseModel
{
    protected $fillable = [
        'company_id', 'name', 'code', 'symbol',
        'position', 'decimal_places', 'is_default',
    ];

    public function getFormattedAttribute(): string
    {
        return $this->position === 'prefix'
            ? $this->symbol . ' {amount}'
            : '{amount} ' . $this->symbol;
    }

    public function formatAmount(float $amount): string
    {
        $formatted = number_format($amount, $this->decimal_places);
        return $this->position === 'prefix'
            ? $this->symbol . $formatted
            : $formatted . ' ' . $this->symbol;
    }
}

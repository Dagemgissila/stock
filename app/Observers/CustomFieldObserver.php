<?php
namespace App\Observers;

use App\Models\CustomField;

class CustomFieldObserver
{
    public function saving(CustomField $cf): void
    {
        if ($cf->type === 'dropdown' && empty($cf->values)) {
            throw new \InvalidArgumentException('Dropdown field must have values.');
        }
    }
}

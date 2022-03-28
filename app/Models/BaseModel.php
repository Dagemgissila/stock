<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Scopes\CompanyScope;

class BaseModel extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
            if (auth()->check() && empty($model->company_id)) {
                $model->company_id = auth()->user()->company_id;
            }
        });
    }
}

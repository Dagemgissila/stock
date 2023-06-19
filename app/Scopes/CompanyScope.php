<?php
namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = $this->resolveCompanyId();
        if ($companyId) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }

    private function resolveCompanyId(): ?int
    {
        // Use request-injected value (set by ApiAuthMiddleware) for reliability
        if (request()->has('_company_id')) {
            return (int) request()->input('_company_id');
        }
        if (auth()->check() && auth()->user()->company_id) {
            return (int) auth()->user()->company_id;
        }
        return null;
    }
}

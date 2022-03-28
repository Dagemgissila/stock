<?php
namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Hashids\Hashids;

class Hash implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): mixed
    {
        return $value ? (new Hashids('', 10))->encode($value) : null;
    }

    public function set($model, string $key, $value, array $attributes): mixed
    {
        return $value;
    }
}

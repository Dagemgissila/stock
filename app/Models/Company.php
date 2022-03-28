<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'logo', 'login_image',
        'country', 'currency_code', 'status', 'is_rtl', 'white_label_complete',
    ];

    public function getLogoUrlAttribute(): string
    {
        return $this->logo
            ? asset('uploads/companies/' . $this->logo)
            : asset('images/light.png');
    }

    public function warehouses() { return $this->hasMany(Warehouse::class); }
    public function users()      { return $this->hasMany(User::class); }
    public function currencies() { return $this->hasMany(Currency::class); }
    public function settings()   { return $this->hasMany(Settings::class); }
}

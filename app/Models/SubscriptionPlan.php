<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = ['name','price','max_companies','max_users','features','expires_at'];
    protected $casts    = ['features'=>'array','expires_at'=>'datetime'];

    public function companies() { return $this->hasMany(Company::class,'plan_id'); }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isInGracePeriod(): bool
    {
        return $this->isExpired() && now()->lessThan($this->expires_at->addDays(7));
    }
}

<?php
namespace App\Models;

class Settings extends BaseModel
{
    protected $fillable = ['company_id','setting_type','name','type','status'];
    protected $casts    = ['status'=>'boolean'];

    public static function getSetting(string $name, int $companyId): mixed
    {
        return static::where('company_id',$companyId)->where('name',$name)->value('status');
    }

    public static function updateSetting(string $name, $value, int $companyId): void
    {
        static::where('company_id',$companyId)->where('name',$name)->update(['status'=>$value]);
    }
}

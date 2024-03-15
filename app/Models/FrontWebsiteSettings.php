<?php
namespace App\Models;
class FrontWebsiteSettings extends BaseModel {
    protected $fillable=['company_id','store_name','store_description','banner_image','logo','primary_color','meta_title','meta_description','is_active'];
    public function company(){return $this->belongsTo(Company::class);}
}

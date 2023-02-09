<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Lang extends Model {
    protected $fillable = ['name','key','flag','is_rtl','status'];
    public function translations() { return $this->hasMany(Translation::class); }
}

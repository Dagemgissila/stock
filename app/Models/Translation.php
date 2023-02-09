<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Translation extends Model {
    protected $fillable = ['lang_id','key','value'];
    public function lang() { return $this->belongsTo(Lang::class); }
}

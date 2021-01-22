<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static findOrFail($proxy_id)
 */
class Proxy extends Model
{
    protected $guarded = [];

    public function crawler()
    {
        return $this->hasOne('App\Crawler');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

}

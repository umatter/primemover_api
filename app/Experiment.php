<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Experiment extends Model
{
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function crawlers()
    {
        return $this->hasMany('App\Crawler');
    }
}

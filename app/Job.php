<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $guarded = [];

    public function crawler()
    {
        return $this->belongsTo('App\Crawler');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function queue()
    {
        return $this->belongsTo('App\Queue');
    }

    public function behaviors()
    {
        return $this->hasMany('App\Behavior');
    }
}

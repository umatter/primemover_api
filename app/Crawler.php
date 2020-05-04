<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Crawler extends Model
{
    protected $guarded = [];

    public function proxy()
    {
        return $this->belongsTo('App\Proxy');
    }

    public function configuration()
    {
        return $this->belongsTo('App\Configuration');
    }

    public function job()
    {
        return $this->belongsTo('App\Job');
    }

    public function agent()
    {
        return $this->belongsTo('App\Agent');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}

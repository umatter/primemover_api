<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Crawler extends Model
{
    protected $guarded = [];

    public function proxy()
    {
        return $this->belongsTo('App\Proxy')->where('active', 1);
    }

    public function configuration()
    {
        return $this->belongsTo('App\Configuration')->where('active', 1);
    }

    public function queues()
    {
        return $this->hasMany('App\Queue')->with('jobs')->where('active', 1);
    }

    public function agent()
    {
        return $this->belongsTo('App\Agent')->where('active', 1);
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}

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
        return $this->belongsTo('App\Configuration')->where('active', 1)->with('params');
    }

    public function queues()
    {
        return $this->hasMany('App\Queue')->with('jobs')->where('active', 1);
    }

    public function queuesProcessed()
    {
        return $this->hasMany('App\Queue')->with('jobs')->where('processed', 1);
    }

    public function queuesProcessing()
    {
        return $this->hasMany('App\Queue')->with('jobs')->where('processing', 1);
    }

    public function queuesReviewed()
    {
        return $this->hasMany('App\Queue')->with('jobs')->where('reviewed', 1);
    }

    public function agent()
    {
        return $this->belongsTo('App\Agent')->where('active', 1);
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function experiment()
    {
        return $this->belongsTo('App\Experiment');
    }

}

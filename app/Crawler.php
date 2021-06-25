<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Crawler extends Model
{
    protected $guarded = [];

    public function proxy(): BelongsTo
    {
        return $this->belongsTo('App\Proxy')->where('active', 1);
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo('App\Configuration')->where('active', 1)->with('params');
    }

    public function queues(): HasMany
    {
        return $this->hasMany('App\Queue')->with('jobs')->where('active', 1);
    }

    public function queuesNew(): HasMany
    {
        return $this->hasMany('App\Queue')->with('jobs')->where('new', 1);
    }

    public function queuesProcessed()
    {
        return $this->hasMany('App\Queue')->with('jobs')->where('processed', 1);
    }

    public function queuesNotProcessed()
    {
        return $this->hasMany('App\Queue')->with('jobs')->where('processed', 0);
    }

    public function queuesProcessing()
    {
        return $this->hasMany('App\Queue')->with('jobs')->where('processing', 1);
    }

    public function queuesNotProcessing()
    {
        return $this->hasMany('App\Queue')->with('jobs')->where('processing', 0);
    }

    public function queuesReviewed()
    {
        return $this->hasMany('App\Queue')->with('jobs')->where('reviewed', 1);
    }

    public function queuesNotReviewed()
    {
        return $this->hasMany('App\Queue')->with('jobs')->where('reviewed', 0);
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

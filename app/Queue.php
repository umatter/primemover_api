<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function crawler()
    {
        return $this->belongsTo('App\Crawler');
    }

    public function jobs()
    {
        return $this->hasMany('App\Job')->with('behaviors', 'reports:id,job_id,path,created_at,updated_at');
    }
}

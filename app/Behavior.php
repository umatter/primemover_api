<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Behavior extends Model
{
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function job()
    {
        return $this->hasOne('App\Job');
    }
}

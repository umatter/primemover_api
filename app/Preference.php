<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function configuration()
    {
        return $this->belongsTo('App\Configuration');
    }

    public function preferencable()
    {
        return $this->morphTo();
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static findOrFail($agent_id)
 */

class Agent extends Model
{
    protected $guarded = [];

    protected $casts = [
        //'multilogin_profile' => 'array',
    ];

    public function crawler()
    {
        return $this->hasOne('App\Crawler');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function preferences()
    {
        return $this->morphMany('App\Preference', 'preferencable');
    }
}

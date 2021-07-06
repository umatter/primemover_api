<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    protected $guarded = [];

    public function crawler(): BelongsTo
    {
        return $this->belongsTo('App\Crawler');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo('App\User');
    }

    public function reports(): HasMany
    {
        return $this->hasMany('App\Report');
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo('App\Queue')->with('jobs');
    }

    public function behaviors(): HasMany
    {
        return $this->hasMany('App\Behavior');
    }
}

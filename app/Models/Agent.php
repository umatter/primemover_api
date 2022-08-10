<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int user_id
 * @property string name
 * @property string description
 * @property string identification
 * @property string multilogin_id
 * @property string multilogin_profile
 * @property string location
 * @property bool active
 */
class Agent extends BaseModel
{
    protected $guarded = [];

    public function crawler(): HasOne
    {
        return $this->hasOne(Crawler::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preferences(): MorphMany
    {
        return $this->morphMany(Preference::class, 'preferencable');
    }
}

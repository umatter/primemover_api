<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int user_id
 * @property string name
 * @property string description
 * @property bool active
 */
class Configuration extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    public function crawler(): HasOne
    {
        return $this->hasOne(Crawler::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function params(): HasMany
    {
        return $this->hasMany(Param::class);
    }

    public function preferences(): MorphMany
    {
        return $this->morphMany(Preference::class, 'preferencable');
    }
}

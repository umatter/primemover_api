<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Experiment extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function crawlers(): HasMany
    {
        return $this->hasMany(Crawler::class);
    }

    public function preferences(): MorphMany
    {
        return $this->morphMany(Preference::class, 'preferencable');
    }
}

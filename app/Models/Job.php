<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends BaseModel
{
    protected $guarded = [];

    public function crawler(): BelongsTo
    {
        return $this->belongsTo(Crawler::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class)
            ->with('jobs');
    }

    public function behaviors(): HasMany
    {
        return $this->hasMany(Behavior::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Queue extends BaseModel
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function crawler(): BelongsTo
    {
        return $this->belongsTo(Crawler::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class)
            ->with('behaviors', 'reports:id,job_id,filename,path,created_at,updated_at');
    }
}

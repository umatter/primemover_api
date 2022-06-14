<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Behavior extends BaseModel
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Proxy extends BaseModel
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
}

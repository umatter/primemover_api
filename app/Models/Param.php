<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Param extends BaseModel
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

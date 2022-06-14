<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Outlet extends BaseModel
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

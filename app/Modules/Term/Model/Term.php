<?php

namespace App\Modules\Term\Model;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Term extends BaseModel
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

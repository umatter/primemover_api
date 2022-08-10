<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int user_id
 * @property int configuration_id
 * @property string name
 * @property string description
 * @property string value
 * @property string preferencable_type
 * @property int preferencable_id
 * @property bool active
 */
class Preference extends BaseModel
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(Configuration::class);
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    public function preferencable(): MorphTo
    {
        return $this->morphTo();
    }
}

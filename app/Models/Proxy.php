<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int user_id
 * @property string name
 * @property string description
 * @property string hostname
 * @property string port
 * @property string username
 * @property string password
 * @property string type
 * @property bool active
 */
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

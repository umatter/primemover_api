<?php

namespace App\Modules\Behavior\Model;

use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Job\Model\Job;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int user_id
 * @property int job_id
 * @property string name
 * @property string description
 * @property string value
 * @property bool active
 */
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

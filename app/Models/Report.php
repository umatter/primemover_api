<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int user_id
 * @property int job_id
 * @property string description
 * @property string path
 * @property string filename
 * @property string filecontent
 * @property string mimetype
 */
class Report extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

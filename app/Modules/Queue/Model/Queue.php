<?php

namespace App\Modules\Queue\Model;

use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Job\Model\Job;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int user_id
 * @property int crawler_id
 * @property string name
 * @property string description
 * @property bool new
 * @property bool processed
 * @property bool processing
 * @property bool reviewed
 * @property string status_code
 * @property string status_message
 * @property bool active
 * @property int order
 * @property Carbon start_at
 * @property Carbon started_at
 * @property Carbon finished_at
 */
class Queue extends BaseModel
{
    use HasFactory;

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

    public function scopeCrawler(Builder $query, int $crawlerId): Builder
    {
        return $query->where('crawler_id', $crawlerId);
    }
}

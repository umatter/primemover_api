<?php

namespace App\Modules\Job\Model;

use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Behavior\Model\Behavior;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Queue\Model\Queue;
use App\Modules\Report\Model\Report;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $user_id
 * @property int $queue_id
 * @property string $name
 * @property string $description
 * @property string $type
 * @property string $status_code
 * @property string $status_message
 * @property string $captcha_found
 * @property string $captcha_solved
 * @property int $order
 * @property bool $active
 * @property Carbon $started_at
 * @property Carbon $start_at
 * @property Carbon $finished_at
 */
class Job extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    public function crawler(): BelongsTo
    {
        return $this->belongsTo(Crawler::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class)
            ->with('jobs');
    }

    public function behaviors(): HasMany
    {
        return $this->hasMany(Behavior::class);
    }

    public function scopeQueue(Builder $query, int $queueId): Builder
    {
        return $query->where('queue_id', $queueId);
    }
}

<?php

namespace App\Modules\Crawler\Model;

use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Proxy\Model\Proxy;
use App\Modules\Queue\Model\Queue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int user_id
 * @property int experiment_id
 * @property int proxy_id
 * @property int agent_id
 * @property int configuration_id
 * @property string name
 * @property string description
 * @property bool active
 * @property bool testing
 * @property bool new
 * @property bool updated
 */
class Crawler extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = [
        'latest_queues_succeeded',
        'latest_queues_failed',
        'latest_queues_count',
    ];

    protected $with = [
        'configuration',
        'agent',
        'proxy',
    ];

    public function proxy(): BelongsTo
    {
        return $this->belongsTo(Proxy::class)
            ->where('active', 1);
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(Configuration::class)
            ->where('active', 1)
            ->with('params');
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->with('jobs')
            ->where('active', 1);
    }

    public function queuesNew(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->with('jobs')
            ->where('new', 1);
    }

    public function queuesProcessed(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->with('jobs')
            ->where('processed', 1);
    }

    public function queuesNotProcessed(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->with('jobs')
            ->where('processed', 0);
    }

    public function queuesProcessing(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->with('jobs')
            ->where('processing', 1);
    }

    public function queuesNotProcessing(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->with('jobs')
            ->where('processing', 0);
    }

    public function queuesReviewed(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->with('jobs')
            ->where('reviewed', 1);
    }

    public function queuesNotReviewed(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->with('jobs')
            ->where('reviewed', 0);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class)
            ->where('active', 1);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    public function getQueuesByStatus(int $hoursBack = 12, string $status = ''): HasMany
    {
        return $this->hasMany(Queue::class)
            ->whereDate('started_at', '>', Carbon::now()->subHours($hoursBack))
            ->when($status, function (Builder $builder) use ($status): void {
                $builder->where('status_code', '=', $status);
            });
    }

    public function getLatestQueuesSucceededAttribute(): int
    {
        return $this->getQueuesByStatus(24, 'success')->count();
    }

    public function getLatestQueuesFailedAttribute(): int
    {
        return $this->getQueuesByStatus(24, 'failed')->count();
    }

    public function getLatestQueuesCountAttribute(): int
    {
        return $this->getQueuesByStatus(24)->count();
    }

    public function scopeExperiment(Builder $query, int $experimentId): Builder
    {
        return $query->where('experiment_id', $experimentId);
    }
}

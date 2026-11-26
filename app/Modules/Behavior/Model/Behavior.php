<?php

namespace App\Modules\Behavior\Model;

use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Job\Model\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @property int    $user_id
 * @property int    $job_id
 * @property string $name
 * @property string $description
 * @property string $value
 * @property bool   $active
 */
class Behavior extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function scopeJob(Builder $query, int $jobId): Builder
    {
        return $query->where('job_id', $jobId);
    }

    /** Every behaviour of every job in one run (queue). */
    public function scopeQueue(Builder $query, int $queueId): Builder
    {
        return $query->whereHas('job', fn (Builder $q) => $q->where('queue_id', $queueId));
    }

    public static function queryBuilder(): QueryBuilder
    {
        return QueryBuilder::for(self::class)
            ->allowedSorts(['id', 'name', 'created_at', 'updated_at'])
            ->defaultSort('-id')
            ->allowedFilters([AllowedFilter::scope('job'), AllowedFilter::scope('queue')]);
    }
}

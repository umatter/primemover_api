<?php

namespace App\Modules\Job\Model;

use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Behavior\Model\Behavior;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Job\Enum\JobType;
use App\Modules\Queue\Model\Queue;
use App\Modules\Report\Model\Report;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\Concerns\ScopesToOwner;

/**
 * @property int            $user_id
 * @property int            $queue_id
 * @property string         $name
 * @property string         $description
 * @property string         $status_code
 * @property string         $status_message
 * @property string         $captcha_found
 * @property string         $captcha_solved
 * @property int            $order
 * @property bool           $active
 * @property Carbon         $started_at
 * @property Carbon         $start_at
 * @property Carbon         $finished_at
 * @property int            $previous_job_id
 * @property Job            $previousJob
 * @property JobType|string $type
 *
 * @method static HasMany reportsWithRemotePath()
 */
class Job extends BaseModel
{
    use ScopesToOwner;

    /**
     * Relation path from this model to the owning experiment.
     *
     * Via the QUEUE, not the crawler: the jobs table has no crawler_id, so
     * Job::crawler() resolves to null and a chain through it would silently
     * match nothing.
     */
    public const OWNERSHIP_PATH = 'queue.crawler.experiment';

    use HasFactory;

    protected $guarded = [];

    protected $appends = [];

    protected $casts = [
        'type' => JobType::class,
    ];

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

    public function previousJob(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'previous_job_id');
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    public function behaviors(): HasMany
    {
        return $this->hasMany(Behavior::class);
    }

    public function scopeQueue(Builder $query, int $queueId): Builder
    {
        return $query->where('queue_id', $queueId);
    }

    public function scopeReportsWithRemotePath(): HasMany
    {
        return $this->reports()->whereNotNull('path');
    }

    public function getReportsWithPathCountAttribute(): int
    {
        return $this->reportsWithRemotePath()->count();
    }

    public function getReportZipFilename(): string
    {
        return 'report-job-'.$this->id.'.zip';
    }

    public static function queryBuilder(): QueryBuilder
    {
        return QueryBuilder::for(self::class)
            ->allowedSorts(['id', 'name', 'type', 'status_code', 'queue_id', 'created_at', 'updated_at'])
            ->defaultSort('-id')
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
                AllowedFilter::scope('queue'),
            ])
            ->withCount(['reports as reports_with_path_count' => function ($query) {
                $query->whereNotNull('path');
            }]);
    }
}

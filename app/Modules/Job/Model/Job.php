<?php

namespace App\Modules\Job\Model;

use App\Models\BaseModel;
use App\Models\Concerns\ScopesToOwner;
use App\Models\User;
use App\Modules\Behavior\Model\Behavior;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Job\Enum\JobType;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Query\StatusQuery;
use App\Modules\Report\Model\Report;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @property int            $user_id
 * @property int            $queue_id
 * @property string         $name
 * @property string         $description
 * @property string         $status_code
 * @property string         $status_message
 * @property string         $captcha_found
 * @property string         $captcha_solved
 * @property null|bool      $decision_degraded
 * @property null|bool      $decision_treatment_lost
 * @property null|string    $decision_selection_mode
 * @property null|array     $decision_report
 * @property int            $order
 * @property bool           $active
 * @property Carbon         $started_at
 * @property Carbon         $start_at
 * @property Carbon         $finished_at
 * @property int            $previous_job_id
 * @property Job            $previousJob
 * @property JobType|string $type
 *
 * The four decision_* columns are the decision function's verdict on this job,
 * copied onto the status PUT by the runner. NULL ON ANY OF THEM MEANS UNKNOWN.
 * It never means clean and it never means false: a job that predates the
 * migration, a job that never called the decision function (a visit, a wait, a
 * type job -- most jobs), and a lambdaselect job run by a runner that has not
 * been redeployed are indistinguishable here and all three are "we cannot
 * tell". A renderer must show "no verdict", not a green tick, and an analysis
 * must count them as their own bucket rather than letting them fall out of a
 * WHERE clause. See docs/integration-contract.md 7.3 and the PHP copy of its
 * rules in App\Modules\Job\Service\DecisionVerdict.
 *
 * @method static HasMany reportsWithRemotePath()
 */
class Job extends BaseModel
{
    use HasFactory;
    use ScopesToOwner;

    /**
     * Relation path from this model to the owning experiment.
     *
     * Via the QUEUE, not the crawler: the jobs table has no crawler_id, so
     * Job::crawler() resolves to null and a chain through it would silently
     * match nothing.
     */
    public const OWNERSHIP_PATH = 'queue.crawler.experiment';

    protected $guarded = [];

    protected $appends = [];

    protected $casts = [
        'type' => JobType::class,
        // Cast, not appended: the columns are real and serialise with the rest
        // of the row on GET /jobs and GET /jobs/{id} (there are no API Resource
        // classes in this codebase -- models serialise whole). The casts exist
        // so the two booleans reach a JSON client as true/false/null rather
        // than 1/0/null, and so decision_report arrives decoded.
        //
        // A primitive cast passes NULL through untouched, which is what keeps
        // "unknown" distinct from "false" all the way to the wire.
        'decision_degraded' => 'boolean',
        'decision_treatment_lost' => 'boolean',
        'decision_report' => 'array',
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

    /**
     * The list query behind GET /jobs.
     *
     * filter[status_code] takes a comma list and expands every value through
     * the alias table, so filter[status_code]=failure also returns the 256 rows
     * this database spells 'failed'. filter[bad]=1 is the contract's is_bad
     * (failure OR warning) computed server-side, so no client has to carry that
     * table -- and so nobody writes the filter as failure alone, which is how
     * the degraded jobs stayed invisible in the first place. sort=severity
     * orders by what the code means. See App\Modules\Queue\Query\StatusQuery.
     */
    public static function queryBuilder(): QueryBuilder
    {
        return QueryBuilder::for(self::class)
            ->allowedSorts([
                'id', 'name', 'type', 'status_code', 'queue_id', 'created_at', 'updated_at',
                StatusQuery::severitySort(),
            ])
            ->defaultSort('-id')
            ->allowedFilters(array_merge([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
                AllowedFilter::scope('queue'),
            ], StatusQuery::filters((new self())->getTable())))
            ->withCount(['reports as reports_with_path_count' => function ($query): void {
                $query->whereNotNull('path');
            }]);
    }
}

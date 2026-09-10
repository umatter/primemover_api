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

    /**
     * The column hasDecisionColumns() probes.
     *
     * The four decision_* columns are added by one migration and dropped by its
     * down(), so they are present or absent together and one probe answers for
     * all four. This is the one every rollup outside this model reads.
     */
    public const DECISION_CAPABILITY_COLUMN = 'decision_treatment_lost';

    /**
     * The column hasSelectedIdColumn() probes.
     *
     * Separate from DECISION_CAPABILITY_COLUMN because it arrives in a DIFFERENT
     * migration and can therefore be absent while the other four are present.
     * Probing the wrong one is how code-before-migration takes the API down --
     * it already has, for 107 minutes -- so each migration gets its own answer.
     */
    public const SELECTED_ID_COLUMN = 'decision_selected_id';

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

    /**
     * Memoised answer to "does the jobs table carry the decision columns yet?".
     *
     * Null means "not asked yet". Resolved at most ONCE per process, because
     * the alternative -- a Schema::hasColumn per crawler row, per queue row --
     * would trade a 500 for a query storm on the same endpoints.
     */
    private static ?bool $hasDecisionColumns = null;

    private static ?bool $hasSelectedIdColumn = null;

    /**
     * Whether jobs.decision_* actually exists in the database in front of us.
     *
     * THIS IS WHY IT EXISTS. The API source is bind-mounted into the running
     * php container on ex63, so new code is live the instant it lands on disk
     * while `php artisan migrate` is a separate, later command. Every rollup
     * added by the status-surfacing change set -- Crawler::$withCount,
     * Queue::jobOutcomeCountRelations(), ExperimentHealth::decisionCounts() --
     * reads a column that does not exist until that command runs, and MySQL
     * answers SQLSTATE[42S22] with an HTTP 500. GET /crawlers-unprocessed is
     * the runner's only work feed and it polls every 60 seconds, so the whole
     * fleet stops fetching work for the length of that window. It has already
     * happened once, for 31 minutes. No deploy ordering can close the window
     * on a bind mount; only the code can, by asking first.
     *
     * A false answer means the rollups are OMITTED, never emitted as 0: absent
     * is unknown, and a 0 treatment-lost count is the positive claim "nothing
     * was lost" about a study nobody has measured. See
     * docs/integration-contract.md 7.3.
     *
     * Memoised in a static rather than a cache on purpose. PHP-FPM gives each
     * request a fresh process, so the memo cannot outlive the request and a
     * "false" recorded before the migration cannot survive it -- which a
     * Cache::rememberForever would, pinning the rollups off for good. A
     * long-lived worker (Octane, a queue worker, an artisan command that spans
     * the migration) is the one case that must call
     * forgetDecisionColumnSupport(); the migration itself does.
     */
    public static function hasDecisionColumns(): bool
    {
        if (null !== self::$hasDecisionColumns) {
            return self::$hasDecisionColumns;
        }

        $model = new self();

        return self::$hasDecisionColumns = $model->getConnection()
            ->getSchemaBuilder()
            ->hasColumn($model->getTable(), self::DECISION_CAPABILITY_COLUMN);
    }

    /**
     * Drop the memo, so the next caller asks the database again.
     *
     * Called by the migration that adds the columns and by the test suite
     * between tests. A long-lived process that runs across a migration needs
     * this; a php-fpm request does not.
     */
    public static function forgetDecisionColumnSupport(): void
    {
        self::$hasDecisionColumns = null;
        self::$hasSelectedIdColumn = null;
    }

    /**
     * Whether jobs.decision_selected_id exists in the database in front of us.
     *
     * Same reasoning as hasDecisionColumns(), and a separate memo for a separate
     * migration: the source is bind-mounted, so this code is live before anyone
     * runs `php artisan migrate`, and writing a column that does not exist yet
     * fails the whole status PUT -- which is the runner reporting a job, not a
     * nicety. Absent means the id is simply not written; it never means the bot
     * watched nothing.
     */
    public static function hasSelectedIdColumn(): bool
    {
        if (null !== self::$hasSelectedIdColumn) {
            return self::$hasSelectedIdColumn;
        }

        $model = new self();

        return self::$hasSelectedIdColumn = $model->getConnection()
            ->getSchemaBuilder()
            ->hasColumn($model->getTable(), self::SELECTED_ID_COLUMN);
    }

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

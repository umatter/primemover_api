<?php

namespace App\Modules\Queue\Model;

use App\Events\ModelUpdateEvent;
use App\Models\BaseModel;
use App\Models\Concerns\ScopesToOwner;
use App\Models\User;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Query\StatusQuery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @property int    $user_id
 * @property int    $crawler_id
 * @property string $name
 * @property string $description
 * @property bool   $new
 * @property bool   $processed
 * @property bool   $processing
 * @property bool   $reviewed
 * @property string $status_code
 * @property string $status_message
 * @property bool   $active
 * @property int    $order
 * @property Carbon $start_at
 * @property Carbon $started_at
 * @property Carbon $finished_at
 */
class Queue extends BaseModel
{
    use HasFactory;
    use ScopesToOwner;

    /**
     * Relation path from this model to the owning experiment.
     *
     * A run hangs off a crawler (queues.crawler_id).
     */
    public const OWNERSHIP_PATH = 'crawler.experiment';

    /**
     * Canonical status codes.
     *
     * The runner writes these; the API, GUI and orchestrator read them. This is
     * the PHP copy of the contract implemented in the orchestrator's
     * orchestrator/status.py -- keep the two in sync. It lives on the Queue
     * model because that is the only status-bearing model both the crawler
     * rollups and the runner update endpoints can reach.
     *
     * Jobs and queues share the same five codes. STATUS_WARNING means the same
     * thing at both levels -- it finished, and something the experiment cares
     * about degraded -- but it is reached for different reasons:
     *
     * - a JOB is warning (2026-08-27) when it ran to completion and its job
     *   data was not written, or the decision function could not apply a filter
     *   the crawler had actually requested (its `degraded` flag);
     * - a QUEUE is warning (2026-08-28) when the run completed but lost jobs
     *   along the way -- JoblistState DONE_JOBSWARNING, with the counts in
     *   status_message ("8/204 jobs succeeded (0 warning, 49 failed, 147
     *   skipped)"). failure stays reserved for a run that produced nothing.
     *
     * Both used to be reported as success, which is exactly what hid silent
     * treatment-arm degradation: two runs of experiment 145 finished 8 of 204
     * jobs and both recorded status_code = success, status_message = "done".
     * This docblock claimed until 2026-09-07 that a queue has no warning state;
     * the runner had been writing it since 2026-08-28 and 73 rows on the
     * production database already had it.
     *
     * Note what does *not* make a job a warning: the decision function
     * answering with selection_mode 'random'. That is a neutral description of
     * how the video was picked, and it is the correct, expected answer for a
     * control crawler that requested no LLM filter at all -- 82% of measured
     * lambda payloads. Treating it as degradation would mark most of the fleet
     * warning and turn this state into noise.
     */
    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_WARNING = 'warning';

    public const STATUS_FAILURE = 'failure';

    public const STATUS_SKIPPED = 'skipped';

    /**
     * Not a status the runner ever writes -- the name of the bucket everything
     * unrecognised falls into.
     *
     * A NULL status_code, an empty one, and a spelling nobody has an alias for
     * yet are all this. The contract's rule is one line long and easy to break
     * by accident: an unrecognised value is UNKNOWN, never success, never
     * "scheduled", and never dropped from a tally.
     */
    public const STATUS_UNKNOWN = 'unknown';

    /**
     * Sort rank per canonical status. Higher is worse. ORDERING ONLY.
     *
     * This is the PHP copy of SEVERITY_SORT_RANK in the orchestrator's
     * orchestrator/status.py, and it is deliberately NOT that module's SEVERITY
     * table: this one carries an 'unknown' rank, wedged between running and
     * warning, and SEVERITY has none, because it answers a different question
     * ("which of these child statuses does the parent inherit?") whose exact
     * numbers other code depends on.
     *
     * Where unknown sits is the whole point. Sorting is where "unknown is never
     * success" is easiest to lose: rank it 0 and every unreported or misspelled
     * row hides at the clean end of the list, which is precisely where nobody
     * looks. Above running and below warning says what is true -- worse than
     * anything still in flight, not yet evidence of damage.
     *
     * @var array<string, int>
     */
    public const SEVERITY_SORT_RANK = [
        self::STATUS_SUCCESS => 0,
        self::STATUS_SKIPPED => 1,
        self::STATUS_RUNNING => 2,
        self::STATUS_UNKNOWN => 3,
        self::STATUS_WARNING => 4,
        self::STATUS_FAILURE => 5,
    ];

    /**
     * The rank of anything SEVERITY_SORT_RANK does not name -- NULL, '' and the
     * next runner typo included.
     */
    public const UNKNOWN_SEVERITY_SORT_RANK = self::SEVERITY_SORT_RANK[self::STATUS_UNKNOWN];

    /**
     * Legacy, misspelled and synonymous status codes, mapped onto the canonical
     * vocabulary.
     *
     * This is the PHP copy of STATUS_ALIASES in the orchestrator's
     * orchestrator/status.py and must stay byte-for-byte equivalent to it --
     * QueueStatusTest::test_the_alias_table_matches_the_orchestrator asserts
     * exactly that, and reads the Python file directly when it is on disk.
     *
     * It carried only 'skpped' and 'failed' until 2026-08-27, which is worse
     * than it sounds: normalizeStatus() is what the runner-facing controllers
     * use to decide whether an incoming report is bad news worth logging, so a
     * runner (or an operator's curl) reporting 'error' or 'fatal' was classified
     * as neither failure nor warning and logged nothing at all -- while the
     * orchestrator, the GUI and every other reader counted the same row as a
     * failure. Divergence here does not error, it just makes one reader blind.
     *
     * 'skpped' is the runner's own typo (JoblistState SKIPPED_*). 'failed' is
     * not written by the current runner -- every JoblistState/JobState failure
     * carries the code 'failure' -- but 277 queue rows and 256 job rows held
     * 'failed' on 2026-08-27, and the model factories still generate it. Any
     * query that matches on status therefore has to accept both spellings or it
     * silently counts a fraction of the fleet.
     *
     * @var array<string, string>
     */
    public const STATUS_ALIASES = [
        'skpped' => self::STATUS_SKIPPED,
        'failed' => self::STATUS_FAILURE,
        'error' => self::STATUS_FAILURE,
        'fatal' => self::STATUS_FAILURE,
        'completed' => self::STATUS_SUCCESS,
        'done' => self::STATUS_SUCCESS,
        'finished' => self::STATUS_SUCCESS,
        'ok' => self::STATUS_SUCCESS,
        'warn' => self::STATUS_WARNING,
        'processing' => self::STATUS_RUNNING,
        'inprogress' => self::STATUS_RUNNING,
        'in_progress' => self::STATUS_RUNNING,
    ];

    /**
     * The job-outcome rollup attributes, in the order the scope adds them.
     *
     * Kept as a constant because they are count SUBSELECTS, not columns: they
     * ride on the model like attributes and are absent from the queues table,
     * so anything comparing a serialised queue against the database has to strip
     * them first. Naming them in one place means the next rollup added does not
     * break a test that happened to list the previous ones by hand.
     *
     * @var string[]
     */
    public const JOB_OUTCOME_COUNT_ATTRIBUTES = [
        'jobs_count',
        'jobs_succeeded_count',
        'jobs_warning_count',
        'jobs_failed_count',
        'jobs_skipped_count',
        'jobs_degraded_count',
        'jobs_treatment_lost_count',
    ];

    /**
     * The two rollups that read a jobs.decision_* column.
     *
     * Absent from the rollup, not zero, until the migration adding those
     * columns has run: 0 treatment-lost jobs is the positive claim "nothing
     * here lost its treatment", and an unmigrated database cannot make it.
     *
     * @var string[]
     */
    public const DECISION_COUNT_ATTRIBUTES = [
        'jobs_degraded_count',
        'jobs_treatment_lost_count',
    ];

    protected $guarded = [];

    protected $casts = [
        'start_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * Canonical form of a raw status_code ('' when there is none).
     *
     * An unrecognised value is returned lowercased rather than mapped onto a
     * canonical code: unknown is unknown, not success.
     */
    public static function normalizeStatus(?string $status): string
    {
        $status = mb_strtolower(trim((string) $status));

        return self::STATUS_ALIASES[$status] ?? $status;
    }

    /**
     * Every raw status_code that should match $status, so a query can whereIn()
     * over the canonical spelling and its legacy synonyms in one go.
     *
     * @return string[]
     */
    public static function statusVariants(string $status): array
    {
        $canonical = self::normalizeStatus($status);
        $variants = [$canonical];

        foreach (self::STATUS_ALIASES as $legacy => $target) {
            if ($target === $canonical) {
                $variants[] = $legacy;
            }
        }

        return $variants;
    }

    /**
     * Sort rank for a raw status_code. Higher is worse.
     *
     * Aliases are resolved first, which is the entire reason this exists:
     * ordering the raw string splits the 256 legacy 'failed' job rows away from
     * the canonical 'failure' ones and drops 'error' and 'fatal' somewhere else
     * again. Anything unrecognised -- including NULL and '' -- ranks as
     * unknown, between running and warning. Never as success.
     *
     * The SQL copy of this function is
     * StatusQuery::severityOrderExpression(); the two must agree, and
     * QueueStatusFilterTest checks that they do row by row.
     */
    public static function severityRank(?string $status): int
    {
        return self::SEVERITY_SORT_RANK[self::normalizeStatus($status)] ?? self::UNKNOWN_SEVERITY_SORT_RANK;
    }

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

    /**
     * Per-queue rollup of job outcomes, as count subselects:
     * jobs_count, jobs_succeeded_count, jobs_warning_count, jobs_failed_count,
     * jobs_skipped_count, jobs_degraded_count, jobs_treatment_lost_count.
     *
     * jobs_warning_count is the one worth showing prominently -- those jobs
     * finished, so nothing else marks them, but the run they belong to is
     * degraded.
     *
     * Deliberately a scope rather than an $appends accessor: the queues list
     * endpoint serves the GUI, and an accessor would fire seven queries per row.
     * Jobs whose status_code is null or 'running' are counted in jobs_count
     * only, which is what makes jobs_count a usable progress denominator.
     *
     * The last two count VERDICTS, not statuses (see
     * docs/integration-contract.md 7.3), and they are a different kind of
     * number from the five above them. jobs.decision_degraded and
     * decision_treatment_lost are three-state: true, false, or NULL meaning
     * UNKNOWN. Only true is counted, so a NULL is not counted in either
     * direction -- and that means a run with jobs_count = 40 and
     * jobs_treatment_lost_count = 0 may hold forty verdicts saying "fine" or
     * forty NULLs saying nothing at all. Do not render 0 as a clean bill of
     * health without checking that a verdict exists.
     */
    public function scopeWithJobOutcomeCounts(Builder $query): Builder
    {
        return $query->withCount(self::jobOutcomeCountRelations());
    }

    /**
     * The same rollup, loaded onto a queue that is already in memory.
     *
     * For the endpoints that resolve a run through route-model binding or fetch
     * a collection without going through queryBuilder() -- GET /queues/{id} and
     * the queues-processed / -unprocessed / -reviewed / -unreviewed family,
     * experiment-scoped variants included. Until this existed, the rollup rode
     * only on GET /queues, so a GUI showing one run had to ask for the whole
     * list again (or issue a second request per run) to put a number on it:
     * /queues had the counts and no jobs, /queues/{id} had the jobs and no
     * counts, and those are the two halves of one question.
     *
     * loadCount() is one extra query for the whole set, not one per count.
     */
    public function loadJobOutcomeCounts(): self
    {
        return $this->loadCount(self::jobOutcomeCountRelations());
    }

    /**
     * The count subselects behind JOB_OUTCOME_COUNT_ATTRIBUTES, defined once so
     * the scope and the eager loader can never disagree about what
     * jobs_failed_count means.
     *
     * @return array<int|string, mixed>
     */
    public static function jobOutcomeCountRelations(): array
    {
        $relations = [
            'jobs',
            'jobs as jobs_succeeded_count' => fn (Builder $jobs) => $jobs
                ->whereIn('status_code', self::statusVariants(self::STATUS_SUCCESS)),
            'jobs as jobs_warning_count' => fn (Builder $jobs) => $jobs
                ->whereIn('status_code', self::statusVariants(self::STATUS_WARNING)),
            'jobs as jobs_failed_count' => fn (Builder $jobs) => $jobs
                ->whereIn('status_code', self::statusVariants(self::STATUS_FAILURE)),
            'jobs as jobs_skipped_count' => fn (Builder $jobs) => $jobs
                ->whereIn('status_code', self::statusVariants(self::STATUS_SKIPPED)),
        ];

        // The last two read columns that exist only after the decision
        // migration has run. The API source is bind-mounted into the running
        // php container, so there is always a window where this code is live
        // and those columns are not; asking for them then is SQLSTATE[42S22]
        // and an HTTP 500 on GET /queues, GET /queues/{id} and all eight
        // queues-* lists. Omitted, the rollup is simply two numbers shorter,
        // which consumers already have to read as "unknown".
        if ( ! Job::hasDecisionColumns()) {
            return $relations;
        }

        // A requested filter did not apply. "A human should look at this."
        $relations['jobs as jobs_degraded_count'] = fn (Builder $jobs) => $jobs
            ->where('decision_degraded', true);

        // The bot was treated and produced control-arm data. The alarm.
        $relations['jobs as jobs_treatment_lost_count'] = fn (Builder $jobs) => $jobs
            ->where('decision_treatment_lost', true);

        return $relations;
    }

    /**
     * The rollup attribute names this database can actually answer.
     *
     * JOB_OUTCOME_COUNT_ATTRIBUTES minus the two decision counts when the
     * columns behind them do not exist yet.
     *
     * @return string[]
     */
    public static function jobOutcomeCountAttributes(): array
    {
        if (Job::hasDecisionColumns()) {
            return self::JOB_OUTCOME_COUNT_ATTRIBUTES;
        }

        return array_values(array_diff(self::JOB_OUTCOME_COUNT_ATTRIBUTES, self::DECISION_COUNT_ATTRIBUTES));
    }

    public static function booted(): void
    {
        self::saved(fn (Model $model) => self::bootedSaved($model));
    }

    public static function bootedSaved(Model $model): void
    {
        // ModelUpdateEvent::dispatch($model);
    }

    /**
     * The list query behind GET /queues.
     *
     * filter[status_code] and filter[bad] are the reason this file changed:
     * status_code was an allowed SORT and not an allowed FILTER, which is the
     * wrong half of the pair. There is no config/query-builder.php in this
     * repo, so Spatie's disable_invalid_filter_query_exception stays false and
     * an unlisted filter key is answered with HTTP 400 -- a researcher asking
     * "show me the runs that need attention" got an error rather than a list,
     * and had to page through everything instead. See
     * App\Modules\Queue\Query\StatusQuery for why neither can compare the raw
     * column.
     */
    public static function queryBuilder(): QueryBuilder
    {
        return QueryBuilder::for(self::class)
            ->withJobOutcomeCounts()
            ->allowedSorts([
                'id', 'name', 'processed', 'processing', 'reviewed', 'status_code', 'crawler_id', 'created_at', 'updated_at',
                // Ordered by what the status MEANS, not by how it is spelled.
                StatusQuery::severitySort(),
            ])
            ->defaultSort('-id')
            ->allowedFilters(array_merge([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
                AllowedFilter::scope('crawler'),
            ], StatusQuery::filters((new self())->getTable())));
    }
}

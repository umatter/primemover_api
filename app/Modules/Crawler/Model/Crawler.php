<?php

namespace App\Modules\Crawler\Model;

use App\Access\Isolation;
use App\Models\BaseModel;
use App\Models\Concerns\ScopesToOwner;
use App\Models\User;
use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Proxy\Model\Proxy;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Query\StatusQuery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as BaseQueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

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
 * @property int  latest_queues_count
 * @property int  latest_queues_succeeded
 * @property int  latest_queues_failed
 * @property int  latest_queues_warning
 * @property int  latest_queues_degraded
 * @property null|int  latest_queues_treatment_lost
 *
 * The six latest_queues_* rollups count runs this bot started inside a rolling
 * window (24 hours by default, ?hours= to widen or narrow it). Two of them are
 * routinely confused, and they answer different questions:
 *
 * - latest_queues_warning counts runs whose OWN status_code is 'warning' (or
 *   the legacy 'warn'). The runner writes that when a run completed but lost
 *   work -- DONE_JOBSWARNING, "8/204 jobs succeeded". It is a statement about
 *   the run.
 * - latest_queues_degraded counts runs holding at least one 'warning' JOB, and
 *   says nothing about the run's own code. A run whose status is 'success' can
 *   be degraded, which is the entire reason that count exists: job data was not
 *   written, or the decision function could not apply a filter the experiment
 *   asked for.
 *
 * They overlap and neither contains the other, so they are never added
 * together. A run that lost half its jobs is warning AND degraded; a run that
 * finished every job while one of them silently produced control-arm data is
 * degraded but not warning; a run the runner reported as warning whose jobs
 * were all written before it gave up is warning but not degraded.
 *
 * Until latest_queues_warning existed (2026-09-07) the first case was counted
 * by nothing at all: succeeded and failed both exclude it and degraded is
 * derived from jobs, so a run the runner had explicitly reported as warning
 * left the GUI showing the bot as "Running" for ever. 73 queue rows on the
 * production database were in exactly that state.
 *
 * latest_queues_treatment_lost is a third question again, and the only one that
 * is about the SCIENCE rather than about the machinery: runs holding at least
 * one job whose decision_treatment_lost is true -- a bot that was configured to
 * be treated and produced control-arm data anyway. Such a run can be, and
 * normally is, 'success' with every job 'success': nothing failed, the video
 * was clicked, the session completed, and the data it produced belongs to the
 * wrong arm. It is counted off a three-state column, so 0 means "no run in the
 * window is known to have lost its treatment", NOT "no run lost it": every job
 * that predates the runner deploy has a NULL verdict and is unknown.
 *
 * "Needs attention" is failure OR warning (docs/integration-contract.md 3.4);
 * it is never failure alone.
 */
class Crawler extends BaseModel
{
    use HasFactory;
    use ScopesToOwner;

    /**
     * Relation path from this model to the owning experiment.
     *
     * A crawler belongs to exactly one experiment (crawlers.experiment_id).
     */
    public const OWNERSHIP_PATH = 'experiment';

    /**
     * Default window, in hours, behind every latest_queues_* attribute. The
     * dashboard calls them "last 24h", so the constant is the contract; a
     * caller that wants another window passes ?hours= (see
     * useLatestQueuesWindow()), and 24 stays the answer for everyone else.
     */
    public const LATEST_QUEUES_HOURS = 24;

    /**
     * Bounds for a caller-supplied ?hours= window: one hour to 30 days.
     *
     * The upper bound is not politeness. Every rollup is a correlated subselect
     * over queues.started_at, so an unbounded window is an unbounded table scan
     * per crawler row; 720 hours is as far back as the dashboard has any use
     * for and as far as this shape of query stays cheap.
     */
    public const LATEST_QUEUES_HOURS_MIN = 1;

    public const LATEST_QUEUES_HOURS_MAX = 720;

    /**
     * Every rollup attribute, in the order they are serialised.
     *
     * Kept as a constant so the accessors, the lazy fallback and the tests all
     * name the same six things. What a given DATABASE can answer is
     * latestQueuesAttributes(), which is this list minus
     * DECISION_ROLLUP_ATTRIBUTES until the decision migration has run; that is
     * the list $appends and $withCount are built from, in the constructor,
     * because a property default cannot ask the schema a question.
     *
     * @var string[]
     */
    public const LATEST_QUEUES_ATTRIBUTES = [
        'latest_queues_succeeded',
        'latest_queues_failed',
        'latest_queues_warning',
        'latest_queues_degraded',
        'latest_queues_treatment_lost',
        'latest_queues_count',
    ];

    /**
     * The rollups that read a jobs.decision_* column.
     *
     * They exist only once the migration adding those columns has run. Before
     * that they are OMITTED -- not zeroed -- which is why they are named apart
     * from the five that only ever read queues.status_code.
     *
     * @var string[]
     */
    public const DECISION_ROLLUP_ATTRIBUTES = [
        'latest_queues_treatment_lost',
    ];

    protected $guarded = [];

    /**
     * Set per instance in the constructor, from latestQueuesAttributes().
     *
     * @var string[]
     */
    protected $appends = [];

    /**
     * The rollups as count subselects on whatever query loads the crawler.
     *
     * This is the fix for a 3-queries-per-row N+1 that fired on /crawlers *and*
     * on /queues (which eager-loads crawler): a 50-row page of either cost 157
     * queries, 150 of them these accessors. As a model-level $withCount it
     * rides along with every newQuery() -- list, show, and eager load alike --
     * so both pages now cost 8, and the fourth and fifth rollups added here
     * (latest_queues_degraded, latest_queues_warning) are free rather than
     * taking it to 257.
     *
     * The accessors below still work on a model loaded without them; they just
     * pay for one aggregate query instead of getting the numbers for free.
     *
     * Empty here and set per instance in the constructor, from
     * latestQueuesWithCount(), because ONE of the six depends on a column that
     * may not exist yet -- and this property is applied by newQuery(), which
     * includes GET /crawlers-unprocessed, the runner's work feed. A property
     * default cannot ask the database; a constructor can.
     *
     * @var string[]
     */
    protected $withCount = [];

    protected $with = [
        'configuration',
        'agent',
        'proxy',
    ];

    /**
     * Memoised fallback rollups for a crawler loaded without the subselects.
     *
     * @var array<string, int>|null
     */
    private ?array $latestQueueCounts = null;

    /**
     * Caller-supplied rollup window, in hours, for the current request.
     *
     * Static because the rollups are count subselects declared in $withCount
     * and built by newQuery(), which no caller can pass an argument to -- the
     * window has to be in scope by the time Eloquent builds the query, and
     * there is no other seam. It is set once per request from ?hours= (see
     * useLatestQueuesWindow()) and null the rest of the time, meaning
     * LATEST_QUEUES_HOURS.
     *
     * PHP-FPM gives each request a fresh process, so this cannot leak between
     * requests; a long-lived worker (Octane, a queue worker) would need to
     * reset it, which is what resetLatestQueuesWindow() is for and what the
     * test suite calls between tests.
     */
    private static ?int $latestQueuesWindowHours = null;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Both lists are order-sensitive (they decide the order the rollups
        // serialise in) and one entry of each is conditional on the schema, so
        // neither can be a property default. Job::hasDecisionColumns() is
        // memoised per process, so this costs one boolean read per hydrated
        // crawler and one schema query per request.
        $this->appends = self::latestQueuesAttributes();
        $this->withCount = self::latestQueuesWithCount();
    }

    /**
     * The rollup attributes this database can actually answer.
     *
     * All six once the decision migration has run; the five status-only ones
     * before that. The missing one is OMITTED rather than appended as 0: the
     * contract's "absent means unknown" (docs/integration-contract.md 7.3) is
     * the whole point -- latest_queues_treatment_lost = 0 asserts that no run
     * in the window lost its treatment, which is a claim about the science
     * that an unmigrated database has no basis for.
     *
     * @return string[]
     */
    public static function latestQueuesAttributes(): array
    {
        if (Job::hasDecisionColumns()) {
            return self::LATEST_QUEUES_ATTRIBUTES;
        }

        return array_values(array_diff(self::LATEST_QUEUES_ATTRIBUTES, self::DECISION_ROLLUP_ATTRIBUTES));
    }

    /**
     * The count subselects behind latestQueuesAttributes(), in the same order.
     *
     * @return string[]
     */
    public static function latestQueuesWithCount(): array
    {
        $relations = [
            'latest_queues_succeeded' => 'queuesInWindowSucceeded as latest_queues_succeeded',
            'latest_queues_failed' => 'queuesInWindowFailed as latest_queues_failed',
            'latest_queues_warning' => 'queuesInWindowWarning as latest_queues_warning',
            'latest_queues_degraded' => 'queuesInWindowDegraded as latest_queues_degraded',
            'latest_queues_treatment_lost' => 'queuesInWindowTreatmentLost as latest_queues_treatment_lost',
            'latest_queues_count' => 'queuesInWindow as latest_queues_count',
        ];

        return array_values(array_intersect_key($relations, array_flip(self::latestQueuesAttributes())));
    }

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

    /**
     * Crawlers this caller, as a runner, is assigned to execute.
     *
     * Partition rule for the two work-fetching endpoints (crawlers-unprocessed,
     * crawlers-testing). Unlike visibleTo() it does NOT honour
     * Isolation::bypasses(): runners are service accounts precisely so they can
     * see everything for status reporting, yet they must never fetch each
     * other's work, because there is no atomic queue claim.
     */
    public function scopeAssignedToCallingRunner(Builder $query): Builder
    {
        $me = Isolation::userId();

        if (null === $me) {
            // No caller, no work. (where(col, null) would compile to IS NULL and
            // hand every unassigned experiment to a guest.)
            return $query->whereRaw('0 = 1');
        }

        return $query->whereHas('experiment', function (Builder $q) use ($me): void {
            $q->where('experiments.runner_user_id', $me);
            if ($me === Isolation::defaultRunnerUserId()) {
                $q->orWhereNull('experiments.runner_user_id');
            }
        });
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

    /**
     * Queues that started within the last $hoursBack hours, optionally narrowed
     * to one status.
     *
     * Two fixes here, both 2026-08-27:
     *
     * - The status match goes through Queue::statusVariants() instead of a bare
     *   equality. Every failing JoblistState carries the code 'failure', so the
     *   old ->where('status_code', '=', 'failed') matched nothing the runner
     *   writes: latest_queues_failed read 0 for every recent run, the GUI's
     *   per-bot progress bars stayed green and no dashboard could show a failed
     *   run. It did still match the older rows that spell it 'failed' (277 of
     *   them on 2026-08-27), which is why statusVariants() accepts both
     *   spellings rather than swapping one literal for the other.
     * - whereDate() compared dates only, so with a 24h window it really meant
     *   "since midnight" and dropped everything from yesterday. started_at is a
     *   varchar holding 'Y-m-d H:i:s' and the Carbon binding is formatted the
     *   same way, so a plain string comparison orders correctly and gives the
     *   rolling window the argument name promises.
     *
     * Note this deliberately does not filter on active/processed: it counts
     * what the bot did in the window, whatever the queue's current flags. The
     * crawler's queues() relation, by contrast, is scoped to active = 1.
     *
     * Kept for ad-hoc windows other than the dashboard's 24 hours. The appended
     * latest_queues_* attributes no longer go through it -- they are count
     * subselects on the loading query (see $withCount) so that a page of
     * crawlers is one query rather than one per row per attribute.
     */
    public function getQueuesByStatus(int $hoursBack = 12, string $status = ''): HasMany
    {
        return $this->hasMany(Queue::class)
            ->where('started_at', '>', Carbon::now()->subHours($hoursBack))
            ->when($status, function (Builder $builder) use ($status): void {
                $builder->whereIn('status_code', Queue::statusVariants($status));
            });
    }

    /**
     * Start of the rollup window. Read at query-build time, so Carbon test time
     * applies and the window really rolls.
     */
    public static function latestQueuesSince(): Carbon
    {
        return Carbon::now()->subHours(self::latestQueuesWindowHours());
    }

    /**
     * The window every latest_queues_* rollup is currently counting over.
     */
    public static function latestQueuesWindowHours(): int
    {
        return self::$latestQueuesWindowHours ?? self::LATEST_QUEUES_HOURS;
    }

    /**
     * Point the rollups at a caller-supplied window and return what was used.
     *
     * Clamped, never rejected: a request is asking for a dashboard, and a 400
     * because someone typed ?hours=0 would be worse than the 1-hour window they
     * nearly asked for. null, an empty string and anything non-numeric all mean
     * "no preference" and restore the 24-hour default, so this is also the
     * reset for a caller that just wants to be sure. The parameter is mixed
     * because it comes straight off the query string, where ?hours[]=3 is a
     * perfectly legal way to hand a model an array.
     */
    public static function useLatestQueuesWindow(mixed $hours): int
    {
        self::$latestQueuesWindowHours = self::clampWindowHours($hours);

        return self::latestQueuesWindowHours();
    }

    /**
     * Restore the default window. Called between tests, and by any long-lived
     * worker that serves more than one request per process.
     */
    public static function resetLatestQueuesWindow(): void
    {
        self::$latestQueuesWindowHours = null;
    }

    /**
     * Every run this bot started inside the window, whatever its current flags.
     *
     * Deliberately unfiltered on active/processed -- it counts what the bot did,
     * not what is still pending. The crawler's queues() relation, by contrast,
     * is scoped to active = 1.
     */
    public function queuesInWindow(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->where('started_at', '>', self::latestQueuesSince());
    }

    public function queuesInWindowSucceeded(): HasMany
    {
        return $this->queuesInWindow()
            ->whereIn('status_code', Queue::statusVariants(Queue::STATUS_SUCCESS));
    }

    public function queuesInWindowFailed(): HasMany
    {
        return $this->queuesInWindow()
            ->whereIn('status_code', Queue::statusVariants(Queue::STATUS_FAILURE));
    }

    /**
     * Runs in the window the runner itself reported as 'warning'.
     *
     * The run completed and lost work along the way -- JoblistState
     * DONE_JOBSWARNING, whose status_message carries the counts ("8/204 jobs
     * succeeded (0 warning, 49 failed, 147 skipped)"). It is the run's own
     * verdict on itself, so unlike queuesInWindowDegraded() it reads the queue
     * row and never looks at the jobs.
     *
     * Matched through statusVariants() so the legacy 'warn' spelling counts
     * too: nothing in the ecosystem normalises on write, so a reader that
     * matches one spelling silently counts a fraction of the fleet.
     */
    public function queuesInWindowWarning(): HasMany
    {
        return $this->queuesInWindow()
            ->whereIn('status_code', Queue::statusVariants(Queue::STATUS_WARNING));
    }

    /**
     * Runs in the window carrying at least one degraded job.
     *
     * A run is degraded, not failed: every job finished, but one of them came
     * back 'warning' -- job data was not written, or the decision function could
     * not apply a filter the experiment asked for. The run's own status_code is
     * 'success' in that case, which is exactly why this cannot be derived from
     * the queue row and has to look at the jobs.
     */
    public function queuesInWindowDegraded(): HasMany
    {
        return $this->queuesInWindow()
            ->whereHas('jobs', fn (Builder $jobs) => $jobs
                ->whereIn('status_code', Queue::statusVariants(Queue::STATUS_WARNING)));
    }

    /**
     * Runs in the window that lost a treatment.
     *
     * At least one job in the run came back with decision_treatment_lost =
     * true: the bot was configured to be treated and produced control-arm data
     * anyway, so that session is scientifically void. This is the alarm the
     * whole error/warning overhaul exists to raise, and it is invisible from
     * every other rollup -- such a run is routinely 'success' with every job
     * 'success', because nothing about it FAILED.
     *
     * Matched on true only. NULL is unknown, and a job that predates the runner
     * deploy, a job that never called the decision function, and a lambdaselect
     * job whose decision function emitted no report are all NULL. A zero here
     * therefore means "nothing in the window is KNOWN to have lost its
     * treatment", which is a weaker statement than it looks.
     */
    public function queuesInWindowTreatmentLost(): HasMany
    {
        return $this->queuesInWindow()
            ->whereHas('jobs', fn (Builder $jobs) => $jobs->where('decision_treatment_lost', true));
    }

    /**
     * Bots whose MOST RECENT run ended with one of $variants.
     *
     * The crawler has no status of its own, so "show me the bots that need
     * attention" has to be answered from its runs, and the only answer a
     * researcher means by it is the latest one: a bot that failed this morning
     * and has succeeded twice since is not the bot to look at.
     *
     * "Latest" is by started_at, tie-broken by id, and runs that have never
     * started are excluded on purpose. This API composes runs ahead of time --
     * a queue is created with a future start_at and no status_code at all --
     * so ranking by id, or letting an unstarted run take part, would let a run
     * scheduled for tomorrow mask the failure that happened last night.
     *
     * Written as EXISTS(latest) AND NOT EXISTS(anything newer) rather than as a
     * scalar subselect because that shape is understood identically by sqlite
     * (the test suite) and MySQL (production) and needs no raw SQL: a LIMIT 1
     * correlated subquery on the left of an IN has to be spliced in by hand,
     * bindings and all.
     *
     * The status match is a plain whereIn over statusVariants(), exactly like
     * every rollup above, so the legacy spellings count and the index on
     * queues.status_code stays usable.
     *
     * @param string[] $variants
     */
    public function scopeLatestRunStatusIn(Builder $query, array $variants): Builder
    {
        if ([] === $variants) {
            return $query;
        }

        return $query->whereExists(function (BaseQueryBuilder $latest) use ($variants): void {
            $latest->selectRaw('1')
                ->from('queues', 'latest_run')
                ->whereColumn('latest_run.crawler_id', 'crawlers.id')
                ->whereNotNull('latest_run.started_at')
                ->where('latest_run.started_at', '<>', '')
                ->whereIn('latest_run.status_code', $variants)
                ->whereNotExists(function (BaseQueryBuilder $newer): void {
                    $newer->selectRaw('1')
                        ->from('queues', 'newer_run')
                        ->whereColumn('newer_run.crawler_id', 'crawlers.id')
                        ->whereNotNull('newer_run.started_at')
                        ->where('newer_run.started_at', '<>', '')
                        ->where(function (BaseQueryBuilder $isNewer): void {
                            $isNewer
                                ->whereColumn('newer_run.started_at', '>', 'latest_run.started_at')
                                ->orWhere(function (BaseQueryBuilder $sameInstant): void {
                                    $sameInstant
                                        ->whereColumn('newer_run.started_at', '=', 'latest_run.started_at')
                                        ->whereColumn('newer_run.id', '>', 'latest_run.id');
                                });
                        });
                });
        });
    }

    public function getLatestQueuesSucceededAttribute(): int
    {
        return $this->latestQueueCount('latest_queues_succeeded');
    }

    public function getLatestQueuesFailedAttribute(): int
    {
        return $this->latestQueueCount('latest_queues_failed');
    }

    /**
     * Runs in the window whose own status_code is 'warning'.
     *
     * Not a subset or a superset of latest_queues_degraded -- see the class
     * docblock. Before this count existed such a run was tallied nowhere:
     * succeeded and failed both exclude it, degraded looks at jobs, and the
     * GUI's bot card fell through to "Running" for a run that had finished
     * hours earlier.
     */
    public function getLatestQueuesWarningAttribute(): int
    {
        return $this->latestQueueCount('latest_queues_warning');
    }

    /**
     * Runs in the last 24h with at least one job in the 'warning' state.
     *
     * The GUI shows this beside succeeded/failed: a bot whose runs all
     * "succeeded" while every session lost its treatment is the failure mode
     * this whole change exists to make visible.
     */
    public function getLatestQueuesDegradedAttribute(): int
    {
        return $this->latestQueueCount('latest_queues_degraded');
    }

    /**
     * Runs in the window holding at least one job that lost its treatment.
     *
     * Counts runs, not jobs: a session that lost the treatment on every one of
     * its forty selects is one void session, and the per-job number is the
     * queue's jobs_treatment_lost_count.
     */
    public function getLatestQueuesTreatmentLostAttribute(): ?int
    {
        if ( ! Job::hasDecisionColumns()) {
            // Nullable purely for this case: the column the rollup counts does
            // not exist, so there is no honest number. It is also not in
            // $appends here, so nothing serialises it -- a direct read gets
            // null ("unknown"), never 0 ("nothing lost").
            return null;
        }

        return $this->latestQueueCount('latest_queues_treatment_lost');
    }

    public function getLatestQueuesCountAttribute(): int
    {
        return $this->latestQueueCount('latest_queues_count');
    }

    public function scopeExperiment(Builder $query, int $experimentId): Builder
    {
        return $query->where('experiment_id', $experimentId);
    }

    /**
     * Drop rollups that were computed for a different window.
     *
     * Needed on the show endpoint only: route-model binding resolves the
     * crawler through newQuery(), so the subselects have already run -- with
     * the default window -- before the controller can read ?hours=. Clearing
     * them sends the accessors down the one-aggregate fallback, which builds
     * its query with the window now in force.
     */
    public function forgetLatestQueueCounts(): self
    {
        foreach (self::LATEST_QUEUES_ATTRIBUTES as $attribute) {
            unset($this->attributes[$attribute]);
        }

        $this->latestQueueCounts = null;

        return $this;
    }

    /**
     * The list query behind GET /crawlers.
     *
     * filter[status_code] and filter[bad] are here for the same reason they are
     * on /queues and /jobs -- Spatie answers an unlisted filter key with HTTP
     * 400, so "show me the bots that need attention" was not merely
     * unsupported, it was an error -- but they mean something different at this
     * level: a crawler has no status of its own, so the filter is applied to
     * its LATEST RUN. A comma list is accepted and every value expanded through
     * the alias table, so filter[status_code]=failure,warning still matches the
     * runs this database spells 'failed'.
     *
     * filter[bad]=1 is exactly that pair, computed from
     * StatusQuery::badVariants() so a bot list, a run list and a job list
     * cannot come to mean different things by "bad". It exists on all three
     * endpoints deliberately: is_bad is the contract's one "a human should look
     * at this" predicate (docs/integration-contract.md 3.4), and a client that
     * has to spell it out per endpoint is a client that will eventually spell
     * it 'failure' alone -- which is the omission that hid every degraded run
     * for as long as it existed.
     *
     * There is deliberately no severity sort here. Severity is a property of a
     * status_code, and the only status_code in play belongs to a row in another
     * table; ordering by it would mean a correlated scalar subselect in ORDER BY
     * on a page that already carries six count subselects, to answer a question
     * the filter answers exactly.
     */
    public static function queryBuilder(): QueryBuilder
    {
        return QueryBuilder::for(self::class)
            ->allowedSorts(['id', 'name', 'experiment_id', 'created_at', 'updated_at'])
            ->defaultSort('-id')
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
                AllowedFilter::scope('experiment'),
                AllowedFilter::callback('status_code', function (Builder $query, $value): void {
                    $query->latestRunStatusIn(StatusQuery::variantsFor($value));
                }),
                AllowedFilter::callback('bad', function (Builder $query, $value): void {
                    if ( ! StatusQuery::wantsBad($value)) {
                        // filter[bad]=0 is "show me everything", not "only the
                        // good ones" -- inventing the inverse would quietly
                        // classify every unknown status as good.
                        return;
                    }

                    $query->latestRunStatusIn(StatusQuery::badVariants());
                }),
            ]);
    }

    /**
     * The requested window in range, or null when nothing usable was asked for.
     */
    private static function clampWindowHours(mixed $hours): ?int
    {
        if (null === $hours || ! is_numeric($hours)) {
            return null;
        }

        return (int) max(self::LATEST_QUEUES_HOURS_MIN, min(self::LATEST_QUEUES_HOURS_MAX, (int) $hours));
    }

    /**
     * One rollup, from the subselect if the query provided it.
     *
     * If it did not -- a crawler built in memory, or hydrated by a query that
     * replaced the select list -- all six are fetched together in a single
     * aggregate and memoised, so the fallback costs one query for the set
     * rather than one per attribute.
     */
    private function latestQueueCount(string $attribute): int
    {
        if (array_key_exists($attribute, $this->attributes)) {
            return (int) $this->attributes[$attribute];
        }

        return $this->loadLatestQueueCounts()[$attribute] ?? 0;
    }

    /**
     * @return array<string, int>
     */
    private function loadLatestQueueCounts(): array
    {
        if (null !== $this->latestQueueCounts) {
            return $this->latestQueueCounts;
        }

        // latestQueuesAttributes(), not LATEST_QUEUES_ATTRIBUTES: on a database
        // without the decision columns the treatment-lost rollup is not one of
        // the numbers this query can ask for, and must stay ABSENT here rather
        // than being filled in as 0 by array_fill_keys.
        $attributes = self::latestQueuesAttributes();

        $counts = array_fill_keys($attributes, 0);

        if (null === $this->getKey()) {
            return $this->latestQueueCounts = $counts;
        }

        // newModelQuery() so the crawler's own $with eager loads do not tag
        // three more queries onto what must stay a single aggregate.
        $row = $this->newModelQuery()
            ->select($this->getTable().'.'.$this->getKeyName())
            ->withCount(self::latestQueuesWithCount())
            ->whereKey($this->getKey())
            ->first();

        $raw = null === $row ? [] : $row->getAttributes();

        foreach ($attributes as $attribute) {
            $counts[$attribute] = (int) ($raw[$attribute] ?? 0);
        }

        return $this->latestQueueCounts = $counts;
    }
}

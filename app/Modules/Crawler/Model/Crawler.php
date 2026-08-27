<?php

namespace App\Modules\Crawler\Model;

use App\Models\BaseModel;
use App\Models\Concerns\ScopesToOwner;
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
     * Window, in hours, behind every latest_queues_* attribute. The dashboard
     * calls them "last 24h", so the constant is the contract.
     */
    public const LATEST_QUEUES_HOURS = 24;

    /**
     * The rollup attributes, in the order they are serialised.
     *
     * Kept as a constant so the accessors, the lazy fallback and the tests all
     * name the same four things; $appends and $withCount below repeat them
     * because PHP property defaults cannot be computed.
     *
     * @var string[]
     */
    public const LATEST_QUEUES_ATTRIBUTES = [
        'latest_queues_succeeded',
        'latest_queues_failed',
        'latest_queues_degraded',
        'latest_queues_count',
    ];

    protected $guarded = [];

    protected $appends = [
        'latest_queues_succeeded',
        'latest_queues_failed',
        'latest_queues_degraded',
        'latest_queues_count',
    ];

    /**
     * The rollups as count subselects on whatever query loads the crawler.
     *
     * This is the fix for a 3-queries-per-row N+1 that fired on /crawlers *and*
     * on /queues (which eager-loads crawler): a 50-row page of either cost 157
     * queries, 150 of them these accessors. As a model-level $withCount it
     * rides along with every newQuery() -- list, show, and eager load alike --
     * so both pages now cost 8, and the fourth rollup added here
     * (latest_queues_degraded) is free rather than taking it to 207.
     *
     * The accessors below still work on a model loaded without them; they just
     * pay for one aggregate query instead of getting the numbers for free.
     *
     * @var string[]
     */
    protected $withCount = [
        'queuesInWindowSucceeded as latest_queues_succeeded',
        'queuesInWindowFailed as latest_queues_failed',
        'queuesInWindowDegraded as latest_queues_degraded',
        'queuesInWindow as latest_queues_count',
    ];

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
        return Carbon::now()->subHours(self::LATEST_QUEUES_HOURS);
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

    public function getLatestQueuesSucceededAttribute(): int
    {
        return $this->latestQueueCount('latest_queues_succeeded');
    }

    public function getLatestQueuesFailedAttribute(): int
    {
        return $this->latestQueueCount('latest_queues_failed');
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

    public function getLatestQueuesCountAttribute(): int
    {
        return $this->latestQueueCount('latest_queues_count');
    }

    public function scopeExperiment(Builder $query, int $experimentId): Builder
    {
        return $query->where('experiment_id', $experimentId);
    }

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
            ]);
    }

    /**
     * One rollup, from the subselect if the query provided it.
     *
     * If it did not -- a crawler built in memory, or hydrated by a query that
     * replaced the select list -- all four are fetched together in a single
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

        $counts = array_fill_keys(self::LATEST_QUEUES_ATTRIBUTES, 0);

        if (null === $this->getKey()) {
            return $this->latestQueueCounts = $counts;
        }

        // newModelQuery() so the crawler's own $with eager loads do not tag
        // three more queries onto what must stay a single aggregate.
        $row = $this->newModelQuery()
            ->select($this->getTable().'.'.$this->getKeyName())
            ->withCount($this->withCount)
            ->whereKey($this->getKey())
            ->first();

        $raw = null === $row ? [] : $row->getAttributes();

        foreach (self::LATEST_QUEUES_ATTRIBUTES as $attribute) {
            $counts[$attribute] = (int) ($raw[$attribute] ?? 0);
        }

        return $this->latestQueueCounts = $counts;
    }
}

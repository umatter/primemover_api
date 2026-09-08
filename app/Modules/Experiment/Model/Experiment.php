<?php

namespace App\Modules\Experiment\Model;

use App\Events\ModelUpdateEvent;
use App\Models\BaseModel;
use App\Models\Concerns\ScopesToOwner;
use App\Models\User;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Preference\Model\Preference;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Query\StatusQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @property int    $user_id
 * @property string $name
 * @property string $description
 * @property string $contact
 * @property bool   $active
 * @property int    $runs_bad_count
 *
 * runs_bad_count is the one number an experiment card needs: how many of this
 * study's runs in the rolling window need a human -- failure OR warning, the
 * contract's is_bad predicate (docs/integration-contract.md 3.4), never failure
 * alone. It is a count SUBSELECT and not an accessor, deliberately: the
 * experiment list is paginated, and an accessor would fire one query per row on
 * the one screen where triage starts. It therefore rides only on the queries
 * that ask for it (queryBuilder() and loadRunsBadCount()); an Experiment loaded
 * by anything else simply does not carry the attribute, rather than carrying a
 * silently wrong one.
 *
 * For the full picture -- the six status buckets over runs and over jobs, the
 * decision verdicts, and the last activity -- see
 * GET /experiments/{id}/health and App\Modules\Experiment\Service\ExperimentHealth.
 */
class Experiment extends BaseModel
{
    use HasFactory;
    use ScopesToOwner;

    /**
     * Relation path from this model to the owning experiment.
     *
     * The experiment IS the ownership root.
     */
    public const OWNERSHIP_PATH = '';

    /**
     * The appended rollup, named once so the model, the repository and the
     * tests cannot disagree about its spelling.
     */
    public const RUNS_BAD_COUNT = 'runs_bad_count';

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The runner account assigned to execute this experiment; null = default.
     */
    public function runner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'runner_user_id');
    }

    public function crawlers(): HasMany
    {
        return $this->hasMany(Crawler::class);
    }

    /**
     * Every run of every bot in this study.
     *
     * A study's runs hang off it through its crawlers (experiment -> crawlers
     * -> queues), which is also how ownership is derived, so this is the same
     * path ScopesToOwner walks in the other direction.
     */
    public function queues(): HasManyThrough
    {
        return $this->hasManyThrough(Queue::class, Crawler::class);
    }

    /**
     * Runs that STARTED inside the rolling window.
     *
     * started_at rather than created_at, because runs are composed ahead of
     * time: a queue exists, with a future start_at and no status_code at all,
     * long before it runs. A window on creation would count work that has not
     * happened yet. The window itself is Crawler's -- the same 24 hours, and
     * the same ?hours= override -- so a crawler's latest_queues_* and an
     * experiment's runs_bad_count always describe the same stretch of time.
     */
    public function queuesInWindow(): HasManyThrough
    {
        return $this->queues()
            ->where('queues.started_at', '>', Crawler::latestQueuesSince());
    }

    /**
     * Runs in the window that need a human: failure OR warning.
     *
     * The variants come from StatusQuery, so the legacy spellings count --
     * 'failed', 'error' and 'fatal' are failures, and 277 queue rows on the
     * production database are spelled 'failed'. Matching the canonical value
     * alone would report most of a study's failures as healthy.
     */
    public function runsBadInWindow(): HasManyThrough
    {
        return $this->queuesInWindow()
            ->whereIn('queues.status_code', StatusQuery::badVariants());
    }

    public function preferences(): MorphMany
    {
        return $this->morphMany(Preference::class, 'preferencable');
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
     * Add runs_bad_count to a query as a subselect (one query, any page size).
     */
    public function scopeWithRunsBadCount(Builder $query): Builder
    {
        return $query->withCount(self::runsBadCountRelation());
    }

    /**
     * The same count, loaded onto an experiment already in memory.
     *
     * For GET /experiments/{id}, where route-model binding has resolved the row
     * before any controller could ask for the rollup. One extra query.
     */
    public function loadRunsBadCount(): self
    {
        return $this->loadCount(self::runsBadCountRelation());
    }

    /**
     * Defined once so the scope and the eager loader can never disagree about
     * what runs_bad_count means.
     *
     * @return string[]
     */
    public static function runsBadCountRelation(): array
    {
        return ['runsBadInWindow as '.self::RUNS_BAD_COUNT];
    }

    public static function queryBuilder(): QueryBuilder
    {
        return QueryBuilder::for(self::class)
            ->withRunsBadCount()
            ->allowedSorts(['id', 'name', 'created_at', 'updated_at'])
            ->defaultSort('-id')
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
            ]);
    }
}

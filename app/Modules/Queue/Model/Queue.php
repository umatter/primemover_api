<?php

namespace App\Modules\Queue\Model;

use App\Events\ModelUpdateEvent;
use App\Models\BaseModel;
use App\Models\Concerns\ScopesToOwner;
use App\Models\User;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Job\Model\Job;
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
     * A queue is only ever running|success|failure|skipped. STATUS_WARNING is a
     * job-only terminal state introduced 2026-08-27: the job ran to completion
     * but something the experiment cares about degraded -- job data was not
     * written, or the decision function could not apply a filter the crawler
     * had actually requested (its `degraded` flag). It used to be reported as
     * success, which is exactly what hid silent treatment-arm degradation.
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
     * jobs_skipped_count.
     *
     * jobs_warning_count is the one worth showing prominently -- those jobs
     * finished, so nothing else marks them, but the run they belong to is
     * degraded.
     *
     * Deliberately a scope rather than an $appends accessor: the queues list
     * endpoint serves the GUI, and an accessor would fire five queries per row.
     * Jobs whose status_code is null or 'running' are counted in jobs_count
     * only, which is what makes jobs_count a usable progress denominator.
     */
    public function scopeWithJobOutcomeCounts(Builder $query): Builder
    {
        return $query->withCount([
            'jobs',
            'jobs as jobs_succeeded_count' => fn (Builder $jobs) => $jobs
                ->whereIn('status_code', self::statusVariants(self::STATUS_SUCCESS)),
            'jobs as jobs_warning_count' => fn (Builder $jobs) => $jobs
                ->whereIn('status_code', self::statusVariants(self::STATUS_WARNING)),
            'jobs as jobs_failed_count' => fn (Builder $jobs) => $jobs
                ->whereIn('status_code', self::statusVariants(self::STATUS_FAILURE)),
            'jobs as jobs_skipped_count' => fn (Builder $jobs) => $jobs
                ->whereIn('status_code', self::statusVariants(self::STATUS_SKIPPED)),
        ]);
    }

    public static function booted(): void
    {
        self::saved(fn (Model $model) => self::bootedSaved($model));
    }

    public static function bootedSaved(Model $model): void
    {
        // ModelUpdateEvent::dispatch($model);
    }

    public static function queryBuilder(): QueryBuilder
    {
        return QueryBuilder::for(self::class)
            ->withJobOutcomeCounts()
            ->allowedSorts(['id', 'name', 'processed', 'processing', 'reviewed', 'status_code', 'crawler_id', 'created_at', 'updated_at'])
            ->defaultSort('-id')
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
                AllowedFilter::scope('crawler'),
            ]);
    }
}

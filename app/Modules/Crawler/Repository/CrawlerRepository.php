<?php

namespace App\Modules\Crawler\Repository;

use App\Access\Isolation;
use App\Facades\Factories\PipelineFactory;
use App\Facades\Services\DataMapper;
use App\Modules\Crawler\Http\Request\CrawlerUploadRequest;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Pipe\Upload\CreateQueue;
use App\Modules\Experiment\Model\DTO\QueueDto;
use Arimolzer\IPStackFinder\Facade\IPStackFinderFacade;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tests\Feature\Modules\Crawler\Repository\CrawlerRepositoryTest;
use Tests\Feature\RunnerAssignment\WorkFeedDueWindowTest;

/**
 * @see CrawlerRepositoryTest
 * @see WorkFeedDueWindowTest
 */
class CrawlerRepository
{
    /**
     * LIKE pattern for the one start_at shape a string comparison orders
     * correctly ('Y-m-d H:i:s'). Anything else counts as due -- see unprocessed().
     */
    private const CANONICAL_START_AT = '____-__-__ __:__:__';

    /** The same shape, for ranking in PHP. */
    private const CANONICAL_START_AT_REGEX = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/';

    public function __construct(protected Crawler $crawler) {}

    public function all(?int $perPage = null): LengthAwarePaginator
    {
        return Crawler::queryBuilder()
            ->visibleTo()
            ->where('testing', 0)
            ->where('active', 1)
            ->with(['experiment'])
            ->fastPaginate($perPage ?? request()->input('per_page', 15));
    }

    public function getById(Crawler $crawler): Crawler
    {
        $crawler->with(
            [
                'experiment',
                'queues',
                'agent.preferences',
                'configuration.preferences',
                'proxy',
                'user',
            ]
        );

        return $crawler;
    }

    public function inactive(): Collection
    {
        return $this->crawler->query()->visibleTo()
            ->where('testing', 0)
            ->where('active', 0)
            ->with(
                [
                    'queues',
                    'agent.preferences',
                    'proxy',
                    'configuration.preferences',
                ]
            )->get();
    }

    /**
     * GET /crawlers-processed -- paginated, like every other list that has no
     * runner behind it.
     *
     * Unpaginated it returned every active bot with every one of its processed
     * runs attached, which on production peaked at 460.5 MB against a 128 MB
     * limit and so answered 500 rather than answering at all. Nothing in the
     * GUI, the runner or the orchestrator calls it, which is why that went
     * unnoticed.
     *
     * unprocessed() below is deliberately NOT paginated -- see the note there.
     */
    public function processed(): LengthAwarePaginator
    {
        return $this->crawler->query()->visibleTo()->where('testing', 0)
            ->where('active', 1)
            ->with(['queues' => function ($q): void {
                $q->where('queues.processed', 1);
            },
            ])
            ->with(
                [
                    'agent.preferences',
                    'proxy',
                    'configuration.preferences',
                ]
            )
            ->whereHas('queues', function ($q): void {
                $q->where('processed', 1);
            })
            // Paged output needs a defined order; without one a row can appear
            // on two pages or on none.
            ->orderByDesc('id')
            ->fastPaginate(request()->input('per_page', 15));
    }

    /**
     * GET /crawlers-unprocessed -- the runner's only work feed: the work that
     * is due, not every queue the runner will ever run.
     *
     * `CrawlerService.java:36` binds this path and takes the rows as the work
     * to do. Never paginate it: a page would silently cap the runner, with no
     * error on either side, and the queues beyond the page would never be
     * picked up -- indistinguishable from an experiment that quietly under-ran.
     *
     * It used to send every unprocessed queue, with all its jobs, behaviours
     * and reports. That is bounded by pending work, and pending work is not
     * small: on 2026-10-06/07 an experiment uploaded nine days ahead and one
     * poll hydrated ~11,084 queues and ~394k jobs (~15 KB each). Every poll
     * 500'd -- out of memory at 512M, then at 2G -- for ~16 hours, and the
     * runner on ex63 started nothing, for every experiment on it.
     *
     * THE RULE. A queue of a feed crawler (assigned to the caller, active, not
     * testing) with active = 1 and processed = 0 is sent when ANY of:
     *   - start_at <= now + lookahead, with no lower bound. The upper bound:
     *     the runner schedules a queue only once start_at < now + its
     *     UpdateInterval, so nothing further out is of use to it yet. No lower
     *     bound: the runner is the only thing that closes a stale queue
     *     (SKIPPED_TIMEDOUT, in JobListRunner.setup), and only one it
     *     receives, so an overdue queue of any age must still be sent or it
     *     stays at processed = 0 forever.
     *   - processing = 1: the runner's 180-minute processing timeout runs only
     *     on queues it receives.
     *   - reviewed = 1: the runner closes these on sight (SKIPPED_REVIEWED).
     *   - start_at NULL, '' or not 'Y-m-d H:i:s': such strings compare wrongly
     *     as text, so they count as due, which is what they were before. The
     *     runner parses the shapes it can and waits until they are due.
     * Selection is by due TIME, not a fixed top-N "most pressing first": a
     * top-N stalls the moment more than N are due at once, or when stale rows
     * fill the top.
     *
     * PRIORITY. Waiting rows (everything not processing) are ranked by tier,
     * then start_at, then id. Tier 0 is runnable: a canonical start_at no
     * older than stale_after_minutes. Tier 1 is everything else -- stale rows
     * the runner can only skip, NULL, '', unparseable -- so poison or stale
     * rows can never take the slots of work that can still run. Inside a tier
     * the most overdue comes first, being closest to the runner's 45-minute
     * loss. Queues within a crawler, and crawlers by their best queue, are
     * returned in this order, because the runner's executor is FIFO.
     *
     * BUDGET. The waiting rows are walked in rank order and sent while the
     * poll stays within max_jobs_per_poll jobs -- the memory guard, since
     * memory scales with jobs -- and max_queues_per_poll queues, a secondary
     * bound. Processing rows count against the job budget but are never cut.
     * The walk stops at the first row that does not fit (no skipping ahead),
     * except that the first waiting row is always sent, so one oversized queue
     * cannot stall the feed. Whatever is held back goes out on later polls,
     * and every poll that holds anything back logs it: error when runnable work
     * already due waits, warning when only not-yet-due or stale work does. This
     * is not a silent pagination cap: it never drops work, only defers the
     * least urgent, and says so.
     *
     * TIME. start_at is a varchar holding the app-timezone wall clock
     * ('Y-m-d H:i:s', Queue::setStartAtAttribute), so the bounds are computed
     * in PHP -- arithmetic in UTC, which is exact across a DST change, then
     * formatted in the app zone -- and compared as strings. Never SQL NOW():
     * MySQL runs on the container clock.
     *
     * CONFIG (config/primemover.php, 'runner'). lookahead_minutes must be at
     * least the largest runner.UpdateInterval of any runner polling this API,
     * plus margin, or queues start late. stale_after_minutes must exceed the
     * runner's MaxRedoMinutes (45), or still-runnable rows rank as stale.
     */
    public function unprocessed(): Collection
    {
        $lookahead = $this->runnerSetting('lookahead_minutes', 30, 15);
        $staleAfter = $this->runnerSetting('stale_after_minutes', 60, 46);
        $queueCap = $this->runnerSetting('max_queues_per_poll', 300, 1, 1000);
        $jobBudget = $this->runnerSetting('max_jobs_per_poll', 10000, 1, 20000);

        // Arithmetic in UTC, where adding minutes is unambiguous across a DST
        // change, then formatted as the stored wall clock. Never SQL NOW().
        $zone = (string) config('app.timezone');
        $now = Carbon::now('UTC');
        $wall = fn (Carbon $t): string => $t->copy()->setTimezone($zone)->format('Y-m-d H:i:s');
        $dueBy = $wall($now->copy()->addMinutes($lookahead));
        $staleBefore = $wall($now->copy()->subMinutes($staleAfter));
        $nowWall = $wall($now);

        // Step 1: one narrow scan of the candidate rows. select() on the
        // Eloquent subquery replaces Crawler's six withCount rollups with the
        // bare id; never pluck() it, which would run it as a separate query.
        $rows = DB::table('queues')
            ->whereIn('queues.crawler_id', $this->feedCrawlers()->select('crawlers.id'))
            // Crawler::queues() applies active = 1 too; the two must agree.
            ->where('queues.active', 1)
            ->where('queues.processed', 0)
            ->where(function (QueryBuilder $w) use ($dueBy): void {
                $w->where('queues.processing', 1)
                    ->orWhere('queues.reviewed', 1)
                    ->orWhereNull('queues.start_at')
                    ->orWhere('queues.start_at', '<=', $dueBy)
                    ->orWhere('queues.start_at', 'not like', self::CANONICAL_START_AT);
            })
            ->get(['queues.id', 'queues.crawler_id', 'queues.start_at', 'queues.processing']);

        [$processing, $waiting] = $rows->partition(fn ($r): bool => 1 === (int) $r->processing);

        $tier = fn ($r): int => (is_string($r->start_at)
            && 1 === preg_match(self::CANONICAL_START_AT_REGEX, $r->start_at)
            && strcmp($r->start_at, $staleBefore) >= 0) ? 0 : 1;
        $waiting = $waiting
            ->map(fn ($r): object => (object) [
                'id' => (int) $r->id,
                'start_at' => $r->start_at,
                'tier' => $tier($r),
            ])
            ->sort(fn ($a, $b): int => [$a->tier, (string) $a->start_at, $a->id] <=> [$b->tier, (string) $b->start_at, $b->id])
            ->values();
        $processingIds = $processing->map(fn ($r): int => (int) $r->id)->values()->all();

        // Job counts only for the rows the walk can reach, through the
        // jobs(queue_id, id) index.
        $candidates = $waiting->take($queueCap + 1);
        $countIds = array_merge($processingIds, $candidates->pluck('id')->all());
        $jobCounts = [] === $countIds ? [] : DB::table('jobs')
            ->whereIntegerInRaw('queue_id', $countIds)
            ->groupBy('queue_id')
            ->selectRaw('queue_id, count(*) as n')
            ->pluck('n', 'queue_id')
            ->all();
        $jobsOf = fn (int $id): int => (int) ($jobCounts[$id] ?? 0);

        $jobsSent = array_sum(array_map($jobsOf, $processingIds));
        $sent = [];
        $boundBy = null;
        foreach ($candidates as $row) {
            // The first row always goes, so one oversized queue cannot stall the feed.
            if ([] !== $sent && count($sent) >= $queueCap) {
                $boundBy = 'queues';

                break;
            }
            if ([] !== $sent && $jobsSent + $jobsOf($row->id) > $jobBudget) {
                $boundBy = 'jobs';

                break;
            }
            $sent[] = $row;
            $jobsSent += $jobsOf($row->id);
        }

        $heldBack = $waiting->slice(count($sent));
        if ($heldBack->isNotEmpty()) {
            $first = $heldBack->first();
            $context = [
                'runner_user_id' => Isolation::userId(),
                'bound_by' => $boundBy,
                'max_queues_per_poll' => $queueCap,
                'max_jobs_per_poll' => $jobBudget,
                'jobs_sent' => $jobsSent,
                'due' => $waiting->count(),
                'sent' => count($sent),
                'held_back' => $heldBack->count(),
                'runnable_sent' => count(array_filter($sent, fn ($r): bool => 0 === $r->tier)),
                'stale_sent' => count(array_filter($sent, fn ($r): bool => 1 === $r->tier)),
                'runnable_held_back' => $heldBack->where('tier', 0)->count(),
                'stale_held_back' => $heldBack->where('tier', 1)->count(),
                'processing_sent' => count($processingIds),
                'lookahead_minutes' => $lookahead,
                'due_by' => $dueBy,
                'stale_before' => $staleBefore,
                'first_held_back_queue_id' => $first->id,
                'first_held_back_start_at' => $first->start_at,
            ];
            $message = '1791358251: crawlers-unprocessed held work back: more is due than one poll may carry; '
                .'the most urgent was sent, the rest waits for later polls';

            // Error only when work that could still run, and is already due,
            // waits: that is lateness. Otherwise only future or stale work waits.
            if (0 === $first->tier && strcmp((string) $first->start_at, $nowWall) <= 0) {
                Log::error($message, $context);
            } else {
                Log::warning($message, $context);
            }
        }

        // Step 2: hydrate only what is sent -- waiting ids in priority order,
        // then processing ids. The position in $ids is the response order.
        $ids = array_merge(array_map(fn ($r): int => $r->id, $sent), $processingIds);
        if ([] === $ids) {
            // Serialises as {"data":[]}.
            return $this->crawler->newCollection();
        }
        $rank = array_flip($ids);
        $crawlerOf = $rows->mapWithKeys(fn ($r): array => [(int) $r->id => (int) $r->crawler_id])->all();
        $crawlerIds = array_values(array_unique(array_map(fn (int $id): int => $crawlerOf[$id], $ids)));

        // Not narrowed with select(): DecisionColumnGuardTest pins the six
        // rollups the runner's response carries.
        $crawlers = $this->feedCrawlers()
            ->whereIntegerInRaw('crawlers.id', $crawlerIds)
            ->with(['queues' => function ($q) use ($ids): void {
                // Re-checked: a runner PUT may close a queue between the two steps.
                $q->whereIntegerInRaw('queues.id', $ids)->where('queues.processed', 0);
            },
            ])
            ->with(
                [
                    'agent.preferences',
                    'proxy',
                    'configuration.preferences',
                ]
            )
            ->get();

        // values() on both sorts below: a re-sorted collection keeps its keys,
        // and a list with gaps in its keys json_encodes as an object, which the
        // runner cannot read. The resource layer happens to re-index nested
        // numeric arrays today (JsonResource::filter); the work feed must not
        // depend on that.
        foreach ($crawlers as $crawler) {
            $crawler->setRelation('queues', $crawler->queues->sortBy(fn ($q): int => $rank[(int) $q->id])->values());
        }

        return $crawlers
            ->filter(fn ($c): bool => $c->queues->isNotEmpty())
            ->sortBy(fn ($c): int => $rank[(int) $c->queues->first()->id])
            ->values();
    }

    /**
     * GET /crawlers-reviewed -- same shape and same absence of callers as
     * processed() above, so the same fix.
     */
    public function reviewed(): LengthAwarePaginator
    {
        return $this->crawler->query()->visibleTo()->where('testing', 1)
            ->where('active', 1)
            ->with(['queues' => function ($q): void {
                $q->where('queues.reviewed', 1);
            },
            ])
            ->with(
                [
                    'agent.preferences',
                    'proxy',
                    'configuration.preferences',
                ]
            )
            ->whereHas('queues', function ($q): void {
                $q->where('reviewed', 1);
            })
            // Paged output needs a defined order; without one a row can appear
            // on two pages or on none.
            ->orderByDesc('id')
            ->fastPaginate(request()->input('per_page', 15));
    }

    public function testing(): LengthAwarePaginator
    {
        return $this->crawler->query()->visibleTo()->assignedToCallingRunner()->where('testing', 1)
            ->where('active', 1)
            ->with(['queues', 'agent.preferences', 'proxy', 'configuration.preferences'])
            ->paginate(10);
    }

    public function save(array $data): Crawler|Model
    {
        return $this->crawler->query()->create($data);
    }

    public function update(array $data, Crawler $crawler): Crawler
    {
        $crawler->update($data);

        return $crawler->fresh();
    }

    /**
     * Disable every production crawler on the platform.
     *
     * Deliberately unscoped, in BOTH statements. This is a platform-wide
     * operation whose route is gated to service accounts via
     * can:administer-platform. Scoping only the SELECT would be worse than
     * leaving it alone: the UPDATE would still disable every user's crawlers
     * while the response showed the caller only their own, hiding the blast
     * radius of what they had just done.
     */
    public function disableProdCrawlers(): Collection
    {
        $this->crawler->query()->where('testing', 0)->update(['active' => 0]);

        return $this->crawler->query()->where('active', 0)->get();
    }

    public function forceDelete(Crawler $crawler): Collection
    {
        $crawler->proxy()->delete();
        $crawler->agent()->delete();
        $crawler->configuration()->delete();
        $crawler->queues()->delete();
        $crawler->delete();

        return $this->crawler->query()->visibleTo()->with([
            'experiment',
            'queues',
            'agent.preferences',
            'proxy',
            'configuration.preferences',
        ])->get();
    }

    public function delete(Crawler $crawler): Collection
    {
        $crawler->delete();

        return $this->crawler->query()->visibleTo()->with(
            [
                'experiment',
                'queues',
                'agent.preferences',
                'proxy',
                'configuration.preferences',
            ]
        )->get();
    }

    public function ip($ip)
    {
        $final = $ip ?: request()->ip();
        if ( ! $this->isLocalAddress($final)) {
            return response()->json([
                'result' => IPStackFinderFacade::get($final),
            ], ResponseAlias::HTTP_OK);
        }

        return $final;
    }

    public function isLocalAddress($ip): bool
    {
        return '::1' === $ip || '127.0.0.1' === $ip;
    }

    public function undo(): Collection
    {
        $this->crawler->query()->where('updated', 1)->delete();

        return $this->crawler->query()->visibleTo()->with(
            [
                'experiment',
                'queues',
                'agent.preferences',
                'proxy',
                'configuration.preferences',
                'user',
            ]
        )->get();
    }

    public function cleanup(): Collection
    {
        $this->crawler->query()->where('testing', 0)->delete();

        return $this->crawler->query()->visibleTo()->with(
            [
                'experiment',
                'queues',
                'agent.preferences',
                'proxy',
                'configuration.preferences',
                'user',
            ]
        )->get();
    }

    /**
     * @throws Exception
     */
    public function upload(CrawlerUploadRequest $request, Crawler $crawler): \Illuminate\Support\Collection
    {
        DB::beginTransaction();
        try {
            $response = DataMapper::disableEnforceType()
                ->removeUndefinedAttributes()
                ->mapList($request->getJsonFileContent(), QueueDto::class)
                ->map(fn (QueueDto $queueDto) => PipelineFactory::create()
                    ->send($queueDto->setCrawler($crawler))
                    ->through([
                        CreateQueue::class,
                    ])
                    ->thenReturn());

            DB::commit();

            return $response;
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('1725638225: Crawler upload failed', ['message' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);

            throw ValidationException::withMessages([
                'upload' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * The crawlers whose work this runner fetches. Column names are qualified
     * because the query also runs as a subquery inside FROM queues.
     */
    private function feedCrawlers(): Builder
    {
        return $this->crawler->query()->visibleTo()->assignedToCallingRunner()
            ->where('crawlers.testing', 0)
            ->where('crawlers.active', 1);
    }

    /**
     * A work-feed setting from config/primemover.php 'runner', clamped.
     *
     * A missing, non-numeric or non-positive value means $default rather than
     * the floor: a config cache built before the key existed must not shrink
     * the lookahead to its minimum or the cap to one queue.
     */
    private function runnerSetting(string $key, int $default, int $min, int $max = PHP_INT_MAX): int
    {
        $value = config('primemover.runner.'.$key);
        $value = is_numeric($value) && (int) $value > 0 ? (int) $value : $default;

        return max($min, min($max, $value));
    }
}

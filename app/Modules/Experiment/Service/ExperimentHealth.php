<?php

namespace App\Modules\Experiment\Service;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Query\StatusQuery;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * "Which of my studies needs attention?", answered in one request.
 *
 * An experiment carries no status of any kind -- no column, no aggregate -- so
 * until this existed the only way to see whether a study was healthy was to
 * page through its crawlers 15 at a time and add the rollups up client-side.
 * A researcher opening the experiment list therefore learnt nothing about any
 * of them, which is why the warning state the whole 2026-08 overhaul introduced
 * never reached the one screen where triage actually starts.
 *
 * Three properties this class is built around:
 *
 * 1. **Alias-aware.** status_code is not a normalised column and never will be
 *    (the API refuses to coerce on write; a 422 on the runner PUT would leave
 *    the row stuck at 'running'), so the same outcome is spelled several ways
 *    in the same table -- 277 queue rows and 256 job rows say 'failed' where the
 *    runner now says 'failure', and 'skpped' is a runner typo that persists.
 *    Grouping is therefore done in SQL by the RAW value and folded into the
 *    canonical vocabulary in PHP through Queue::normalizeStatus(), which is the
 *    one place the alias table lives.
 * 2. **Unknown is reported, never folded away.** Anything that normalises to
 *    none of the five canonical codes -- a NULL status_code (the jobs table
 *    holds thousands), an empty string, tomorrow's runner typo -- lands in the
 *    'unknown' bucket and is counted there. It is never silently added to
 *    success, and never dropped, so the six buckets always sum to total and a
 *    consumer can tell "nothing wrong" from "we cannot tell".
 * 3. **A constant number of queries.** Five, whatever the experiment's size:
 *    runs grouped, jobs grouped, the two decision sums, the bot count and the
 *    last activity. Never one per crawler and never one per run -- the shape
 *    that made client-side summing untenable in the first place.
 *
 * "Needs attention" is failure OR warning (docs/integration-contract.md 3.4),
 * taken from StatusQuery::BAD_STATUSES so this class cannot drift from what
 * filter[bad]=1 returns. It is never failure alone.
 */
class ExperimentHealth
{
    /**
     * The five codes the runner writes. Everything else is 'unknown'.
     *
     * @var string[]
     */
    public const CANONICAL_STATUSES = [
        Queue::STATUS_RUNNING,
        Queue::STATUS_SUCCESS,
        Queue::STATUS_WARNING,
        Queue::STATUS_FAILURE,
        Queue::STATUS_SKIPPED,
    ];

    /**
     * The buckets of a runs/jobs tally, in serialisation order.
     *
     * 'total' is deliberately the sum of the six that follow it and not a
     * separately counted number: a count that could disagree with its own parts
     * is how a tally starts losing rows quietly.
     *
     * @var string[]
     */
    public const BUCKETS = [
        'total',
        Queue::STATUS_RUNNING,
        Queue::STATUS_SUCCESS,
        Queue::STATUS_WARNING,
        Queue::STATUS_FAILURE,
        Queue::STATUS_SKIPPED,
        Queue::STATUS_UNKNOWN,
    ];

    /**
     * The requested window in hours, clamped, with a default for nonsense.
     *
     * Clamped rather than rejected, exactly like Crawler's ?hours=: this
     * endpoint answers a dashboard, and a 400 because someone typed ?hours=0
     * would be worse than the one-hour window they nearly asked for. The bounds
     * are Crawler's own constants, because there is one window contract in this
     * API and two places that read it -- 1 hour to 30 days, defaulting to 24.
     *
     * Anything non-numeric (absent, '', 'all', an array from ?hours[]=3) means
     * "no preference" and yields the default rather than an error.
     */
    public static function clampHours(mixed $hours): int
    {
        if ( ! is_numeric($hours)) {
            return Crawler::LATEST_QUEUES_HOURS;
        }

        return (int) max(
            Crawler::LATEST_QUEUES_HOURS_MIN,
            min(Crawler::LATEST_QUEUES_HOURS_MAX, (int) $hours)
        );
    }

    /**
     * The whole health object for one experiment.
     *
     * @param  mixed                $hours raw ?hours= off the query string; clamped here
     * @return array<string, mixed>
     */
    public function for(Experiment $experiment, mixed $hours = null): array
    {
        $windowHours = self::clampHours($hours);
        $since = Carbon::now()->subHours($windowHours);
        $experimentId = (int) $experiment->getKey();

        $runs = self::bucket($this->runStatusCounts($experimentId, $since));
        $jobs = self::bucket($this->jobStatusCounts($experimentId, $since));
        $decisions = $this->decisionCounts($experimentId, $since);

        $health = [
            'experiment_id' => $experimentId,
            'window_hours' => $windowHours,
            'bots' => $this->botCount($experimentId),
            'runs' => $runs,
            'jobs' => $jobs,
            'bad_runs' => self::badTotal($runs),
            'bad_jobs' => self::badTotal($jobs),
        ];

        // Absent, not zero. decisionCounts() returns null on a database that
        // has not run the decision migration, and 'treatment_lost_jobs' => 0
        // would tell the researcher looking at the triage screen that nothing
        // in this study lost its treatment -- a statement about the science
        // that nobody has measured. A missing key is the contract's "unknown"
        // (docs/integration-contract.md 7.3) and the GUI already renders it.
        if (null !== $decisions) {
            $health['treatment_lost_jobs'] = $decisions['treatment_lost'];
            $health['degraded_jobs'] = $decisions['degraded'];
        }

        $health['bots_not_starting'] = $this->botsNotStarting($experimentId, $since);

        $health['last_finished_at'] = $this->lastFinishedAt($experimentId);

        return $health;
    }

    /**
     * Fold grouped (status_code, count) rows into the canonical buckets.
     *
     * The fold is the point: the database hands back 'failed' and 'failure' as
     * two rows describing one outcome, and a tally that reported them
     * separately would under-report failures by more than half on this data.
     * Everything that is not one of the five canonical codes after
     * normalizeStatus() -- NULL, '', a spelling nobody has an alias for --
     * increments 'unknown'. Never success, and never nothing.
     *
     * The input is a LIST OF PAIRS and not a status => count map, which looks
     * like a detail and is not: SQL groups NULL and '' separately, and PHP
     * casts a null array key to ''. Keying by the raw status therefore made the
     * two collide and silently dropped one of them -- losing rows out of a
     * tally whose entire job is to lose none.
     *
     * @param  array<int, array{0: string|null, 1: int|string}> $rows
     * @return array<string, int>
     */
    public static function bucket(array $rows): array
    {
        $counts = array_fill_keys(self::BUCKETS, 0);

        foreach ($rows as [$status, $count]) {
            $canonical = Queue::normalizeStatus($status);
            $bucket = in_array($canonical, self::CANONICAL_STATUSES, true)
                ? $canonical
                : Queue::STATUS_UNKNOWN;

            $counts[$bucket] += (int) $count;
            $counts['total'] += (int) $count;
        }

        return $counts;
    }

    /**
     * failure + warning, off a bucketed tally.
     *
     * Reads StatusQuery::BAD_STATUSES rather than naming the two states, so
     * this number and filter[bad]=1 cannot come to mean different things.
     *
     * @param array<string, int> $counts
     */
    public static function badTotal(array $counts): int
    {
        $bad = 0;

        foreach (StatusQuery::BAD_STATUSES as $status) {
            $bad += $counts[$status] ?? 0;
        }

        return $bad;
    }

    /**
     * Runs of this experiment that STARTED inside the window.
     *
     * started_at, not created_at: this API composes runs ahead of time, so a
     * queue exists (with a future start_at and no status_code) long before it
     * runs. Counting composed-but-unstarted runs would report a study as
     * healthy in proportion to how much work it has queued up.
     *
     * started_at is a varchar holding 'Y-m-d H:i:s' and Carbon binds in exactly
     * that format, so the string comparison is chronological. A NULL fails it,
     * which is what excludes the unstarted runs.
     */
    private function runsInWindow(int $experimentId, Carbon $since): Builder
    {
        return DB::table('queues')
            ->join('crawlers', 'crawlers.id', '=', 'queues.crawler_id')
            ->where('crawlers.experiment_id', $experimentId)
            ->where('queues.started_at', '>', $since);
    }

    /**
     * Jobs belonging to those runs.
     *
     * Scoped through the QUEUE's start, not the job's own: the tally has to be
     * "what did the runs in this window come to", or runs.total and jobs.total
     * would describe two different sets of work and their bad counts could not
     * be read side by side. It also means a job that never started (skipped
     * after an earlier failure, or still pending) is counted -- as skipped or
     * as unknown, which is exactly what it is.
     */
    private function jobsInWindow(int $experimentId, Carbon $since): Builder
    {
        return DB::table('jobs')
            ->join('queues', 'queues.id', '=', 'jobs.queue_id')
            ->join('crawlers', 'crawlers.id', '=', 'queues.crawler_id')
            ->where('crawlers.experiment_id', $experimentId)
            ->where('queues.started_at', '>', $since);
    }

    /**
     * @return array<int, array{0: string|null, 1: int}>
     */
    private function runStatusCounts(int $experimentId, Carbon $since): array
    {
        return self::pairs(
            $this->runsInWindow($experimentId, $since)
                ->groupBy('queues.status_code')
                ->select('queues.status_code', DB::raw('count(*) as aggregate'))
                ->get()
        );
    }

    /**
     * @return array<int, array{0: string|null, 1: int}>
     */
    private function jobStatusCounts(int $experimentId, Carbon $since): array
    {
        return self::pairs(
            $this->jobsInWindow($experimentId, $since)
                ->groupBy('jobs.status_code')
                ->select('jobs.status_code', DB::raw('count(*) as aggregate'))
                ->get()
        );
    }

    /**
     * Grouped rows as (status_code, count) pairs.
     *
     * Deliberately not ->pluck('aggregate', 'status_code'): PHP casts a null
     * array key to '', so the NULL group and the empty-string group -- two
     * separate groups in SQL, both of them 'unknown' -- would overwrite one
     * another and one bucket's worth of rows would vanish.
     *
     * @param  iterable<int, object>                     $rows
     * @return array<int, array{0: string|null, 1: int}>
     */
    private static function pairs(iterable $rows): array
    {
        $pairs = [];

        foreach ($rows as $row) {
            $pairs[] = [
                null === $row->status_code ? null : (string) $row->status_code,
                (int) $row->aggregate,
            ];
        }

        return $pairs;
    }

    /**
     * The two decision verdicts, summed over the same jobs. ONE query.
     *
     * These count VERDICTS, not statuses, and the columns are three-state:
     * true, false, or NULL meaning UNKNOWN. Only true is summed, so a NULL is
     * counted in neither direction -- and a study whose every job predates the
     * runner deploy reports 0 here while knowing nothing at all. Zero is "no
     * job in the window is KNOWN to have lost its treatment", which is weaker
     * than it looks; see docs/integration-contract.md 7.3.
     *
     * Null when the jobs table has no decision_* columns at all -- the window
     * between this code going live on the bind-mounted API source and
     * `php artisan migrate` running. Asking for them then is SQLSTATE[42S22]
     * and a 500 on GET /experiments/{id}/health; answering 0 would be worse
     * than the 500, because it is a false all-clear rather than an outage.
     *
     * @return null|array{degraded: int, treatment_lost: int}
     */
    private function decisionCounts(int $experimentId, Carbon $since): ?array
    {
        if ( ! Job::hasDecisionColumns()) {
            return null;
        }

        $row = $this->jobsInWindow($experimentId, $since)
            ->selectRaw('sum(case when jobs.decision_degraded = 1 then 1 else 0 end) as degraded_jobs')
            ->selectRaw('sum(case when jobs.decision_treatment_lost = 1 then 1 else 0 end) as treatment_lost_jobs')
            ->first();

        return [
            'degraded' => (int) ($row->degraded_jobs ?? 0),
            'treatment_lost' => (int) ($row->treatment_lost_jobs ?? 0),
        ];
    }

    /**
     * Bots configured on this experiment.
     *
     * Deliberately NOT window-scoped: it is the denominator a reader needs to
     * make sense of the run counts ("12 bots, 3 runs in 24h" says something
     * quite different from "3 bots, 3 runs"), and a study's size is a property
     * of the study, not of the window.
     */
    /**
     * Bots whose latest finished run in the window never started, newest
     * streak first.
     *
     * One failed start is noise; a bot whose last five runs all failed before
     * the first job is dead, and nothing said so. In experiment 240 four bots
     * (three with a profile lock Multilogin never released, one with a dead
     * proxy exit) failed 129 runs in a row over a week: each run was a separate
     * 'failure' row with the same message, and no number anywhere counted them
     * per bot.
     *
     * A run "never started" if it ended 'failure' and none of its jobs ran --
     * every job has no status or is 'skipped'. `consecutive` counts such runs
     * back from the bot's latest finished run to its last run that did start,
     * inside the window only: a streak older than the window reads as the
     * window's worth, and `since` is then the window's first such run.
     *
     * @return array<int, array{crawler_id: int, name: ?string, consecutive: int, since: ?string, latest_run_id: int, reason: ?string}>
     */
    private function botsNotStarting(int $experimentId, Carbon $since): array
    {
        $ran = DB::table('jobs')
            ->whereColumn('jobs.queue_id', 'queues.id')
            ->whereNotNull('jobs.status_code')
            ->whereNotIn('jobs.status_code', Queue::statusVariants(Queue::STATUS_SKIPPED));

        $runs = $this->runsInWindow($experimentId, $since)
            ->where('queues.processed', 1)
            ->orderBy('queues.crawler_id')
            ->orderByDesc('queues.started_at')
            ->orderByDesc('queues.id')
            ->select(
                'queues.id',
                'queues.crawler_id',
                'crawlers.name as crawler_name',
                'queues.status_code',
                'queues.status_message',
                'queues.started_at'
            )
            ->selectRaw('exists('.$ran->toSql().') as any_job_ran', $ran->getBindings())
            ->get();

        $bots = [];
        $closed = [];

        foreach ($runs as $run) {
            $botId = (int) $run->crawler_id;

            if (isset($closed[$botId])) {
                continue;
            }

            $neverStarted = Queue::STATUS_FAILURE === Queue::normalizeStatus($run->status_code)
                && ! $run->any_job_ran;

            if ( ! $neverStarted) {
                // The newest run that did start ends the streak, if there is one.
                $closed[$botId] = true;

                continue;
            }

            if ( ! isset($bots[$botId])) {
                $bots[$botId] = [
                    'crawler_id' => $botId,
                    'name' => $run->crawler_name,
                    'consecutive' => 0,
                    'since' => null,
                    'latest_run_id' => (int) $run->id,
                    'reason' => null === $run->status_message ? null : (string) $run->status_message,
                ];
            }

            $bots[$botId]['consecutive']++;
            $bots[$botId]['since'] = self::isoOrRaw($run->started_at);
        }

        $bots = array_values($bots);

        usort($bots, static fn (array $a, array $b): int => [$b['consecutive'], $a['crawler_id']]
            <=> [$a['consecutive'], $b['crawler_id']]);

        return $bots;
    }

    private function botCount(int $experimentId): int
    {
        return DB::table('crawlers')->where('experiment_id', $experimentId)->count();
    }

    /**
     * When this experiment last finished a run -- ACROSS ALL TIME.
     *
     * The one field here that is deliberately not window-scoped, because it is
     * the field that makes an empty window readable: with everything else zero,
     * "last finished 5 days ago" and "never ran" are completely different
     * situations and a window-scoped null could not tell them apart.
     *
     * finished_at is a varchar in 'Y-m-d H:i:s', so MAX() over it is
     * chronological; NULL and '' are excluded rather than trusted to sort low.
     * The value is re-serialised with toIso8601String(), which is what
     * BaseModel::serializeDate() does to every other timestamp this API emits,
     * so a consumer can compare this field against a run's own finished_at
     * without knowing there are two code paths behind them. An unparseable
     * value is handed back as stored rather than dropped.
     */
    private function lastFinishedAt(int $experimentId): ?string
    {
        $latest = DB::table('queues')
            ->join('crawlers', 'crawlers.id', '=', 'queues.crawler_id')
            ->where('crawlers.experiment_id', $experimentId)
            ->whereNotNull('queues.finished_at')
            ->where('queues.finished_at', '<>', '')
            ->max('queues.finished_at');

        if (null === $latest || '' === $latest) {
            return null;
        }

        return self::isoOrRaw($latest);
    }

    /**
     * A stored timestamp as ISO 8601 with its offset, or as stored if it does
     * not parse; null stays null.
     */
    private static function isoOrRaw(mixed $stored): ?string
    {
        if (null === $stored || '' === $stored) {
            return null;
        }

        try {
            return Carbon::parse((string) $stored)->toIso8601String();
        } catch (Throwable) {
            return (string) $stored;
        }
    }
}

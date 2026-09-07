<?php

namespace App\Console\Commands;

use App\Modules\Job\Model\Job;
use App\Modules\Job\Service\JobDecisionService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Copy the decision function's verdict out of MinIO and onto the job rows.
 *
 * The runner writes the verdict onto the status PUT from now on, so this
 * command is about the past: ~54,000 lambda-jobdata objects were written before
 * the columns existed, and the answer to "did this study's treatment arm
 * actually get treated?" is inside every one of them and joinable from none.
 *
 * Three rules, and the first two are the reason it can be run against
 * production without a maintenance window:
 *
 * 1. It never overwrites a non-null value unless --force. A verdict the runner
 *    wrote at the time beats a verdict reconstructed later from an artifact.
 * 2. A payload with NO degradation report writes nothing at all. Those objects
 *    came from a decision function deployed before the report existed, so the
 *    honest value is NULL/unknown; writing false would assert that ~31,000
 *    historical jobs applied treatments nobody measured.
 * 3. An unreadable object is counted and reported, never written as a verdict.
 *    A store that will not answer is missing evidence about a job, never
 *    evidence that the job was fine.
 *
 * Running it twice changes nothing the second time: rule 1 excludes everything
 * rule 2 wrote, and rule 2 keeps skipping the reportless payloads (which is a
 * no-op, not a rewrite).
 */
class BackfillDecisions extends Command
{
    protected $signature = 'decisions:backfill
        {--dry-run : Read and report, write nothing}
        {--limit=0 : Stop after this many jobs (0 = no limit)}
        {--chunk=200 : Jobs held in memory at once}
        {--job=* : Only these job ids}
        {--force : Re-read jobs that already carry a verdict and overwrite it}';

    protected $description = 'Populate jobs.decision_* from the lambda-jobdata objects in the runner object store';

    public function handle(JobDecisionService $decisions): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $limit = max(0, (int) $this->option('limit'));
        $chunk = max(1, (int) $this->option('chunk'));

        $counts = [
            'scanned' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'no_verdict' => 0,
            'unreadable' => 0,
        ];

        if ($dryRun) {
            $this->comment('Dry run: nothing will be written.');
        }

        $this->query($decisions, $force)
            ->chunkById($chunk, function (Collection $jobs) use ($decisions, $dryRun, $force, $limit, &$counts): bool {
                foreach ($jobs as $job) {
                    if (0 !== $limit && $counts['scanned'] >= $limit) {
                        return false;
                    }

                    $counts['scanned']++;
                    $this->backfill($job, $decisions, $dryRun, $force, $counts);
                }

                return 0 === $limit || $counts['scanned'] < $limit;
            });

        $this->report($counts, $dryRun);

        return self::SUCCESS;
    }

    /**
     * Jobs worth reading an object for.
     *
     * Only jobs that actually have a lambda-jobdata report -- the reports table
     * is the index into a flat bucket with no per-job prefix -- and, unless
     * --force, only those whose four columns are all still NULL. "All four" and
     * not "any": a row carrying a verdict is a row this command has no business
     * touching, whoever wrote it.
     */
    private function query(JobDecisionService $decisions, bool $force): Builder
    {
        $query = Job::query()->select('jobs.*');

        $ids = array_filter(array_map('intval', (array) $this->option('job')));

        if ([] !== $ids) {
            $query->whereIn('jobs.id', $ids);
        }

        $decisions->whereHasJobData($query);

        if ( ! $force) {
            $query->whereNull('decision_degraded')
                ->whereNull('decision_treatment_lost')
                ->whereNull('decision_selection_mode')
                ->whereNull('decision_report');
        }

        return $query;
    }

    /**
     * @param array<string, int> $counts
     */
    private function backfill(Job $job, JobDecisionService $decisions, bool $dryRun, bool $force, array &$counts): void
    {
        try {
            $payload = $decisions->payloadFor($job);
        } catch (Throwable $exception) {
            $counts['unreadable']++;
            $this->line("  job {$job->id}: unreadable -- ".$exception->getMessage());

            return;
        }

        if (null === $payload) {
            // The query only selects jobs that have one, so this is a report row
            // that lost its object between the two queries. Same class of answer
            // as unreadable: we cannot tell.
            $counts['unreadable']++;
            $this->line("  job {$job->id}: the lambda-jobdata report disappeared while reading");

            return;
        }

        $verdict = $payload->verdict();
        $columns = $decisions->columnsFor($verdict);

        if ([] === $columns) {
            // A legacy payload: no degraded, no treatment_lost, no filters. The
            // verdict is UNKNOWN and NULL is how this schema spells that.
            $counts['no_verdict']++;

            return;
        }

        if ( ! $force) {
            // Belt and braces against a concurrent runner PUT landing between
            // the chunk query and this write.
            $columns = array_filter(
                $columns,
                fn (string $column): bool => null === $job->getAttribute($column),
                ARRAY_FILTER_USE_KEY
            );

            if ([] === $columns) {
                $counts['unchanged']++;

                return;
            }
        }

        $counts['updated']++;

        // One line per job would be 54,000 lines on the first real run, so the
        // routine case is verbose-only; the failures above always print.
        if ($this->getOutput()->isVerbose()) {
            $this->line(sprintf(
                '  job %d: %s (mode %s)',
                $job->id,
                $verdict->label(),
                '' === $verdict->selectionMode ? '-' : $verdict->selectionMode
            ));
        }

        if ( ! $dryRun) {
            // timestamps off: this is archaeology, not activity. Touching
            // updated_at on tens of thousands of historical jobs would push
            // every one of them to the top of ?sort=-updated_at and make the
            // whole archive look like it changed the day the backfill ran.
            $job->timestamps = false;
            $job->forceFill($columns)->save();
            $job->timestamps = true;
        }
    }

    /**
     * @param array<string, int> $counts
     */
    private function report(array $counts, bool $dryRun): void
    {
        $this->newLine();
        $this->table(
            ['jobs scanned', $dryRun ? 'would update' : 'updated', 'already had one', 'no verdict in payload', 'unreadable'],
            [[
                $counts['scanned'],
                $counts['updated'],
                $counts['unchanged'],
                $counts['no_verdict'],
                $counts['unreadable'],
            ]]
        );

        if ($counts['no_verdict'] > 0) {
            $this->comment(
                $counts['no_verdict'].' payload(s) carried no degradation report and were left NULL. '
                .'That is unknown, not clean: those decisions came from a function deployed before the report existed.'
            );
        }

        if ($counts['unreadable'] > 0) {
            $this->warn(
                $counts['unreadable'].' object(s) could not be read. Missing evidence about those jobs, '
                .'not evidence that they were fine.'
            );
        }
    }
}

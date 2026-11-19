<?php

namespace App\Console\Commands;

use App\Modules\Queue\Model\Queue;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Mark the never-run jobs of runs that ended before this API did it itself.
 *
 * Since Queue::skipUnstartedJobs() the runner's report of a run that ended
 * 'failure' or 'skipped' marks that run's status-less jobs 'skipped'. Runs
 * reported before that still hold them with no status at all -- 13,290 jobs in
 * experiment 240 alone -- and this is the same rule applied to those rows.
 *
 * It writes only jobs with no status_code, of runs that are processed, so it
 * can be repeated and stopped at any point.
 */
class SkipUnstartedJobs extends Command
{
    protected $signature = 'jobs:skip-unstarted
        {--dry-run : Count and report, write nothing}
        {--experiment=* : Only runs of these experiment ids}
        {--chunk=200 : Runs held in memory at once}';

    protected $description = "Mark the status-less jobs of finished 'failure'/'skipped' runs as skipped";

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));
        $experiments = array_map('intval', (array) $this->option('experiment'));

        $runs = 0;
        $jobs = 0;

        if ($dryRun) {
            $this->comment('Dry run: nothing will be written.');
        }

        $this->query($experiments)
            ->chunkById($chunk, function (Collection $queues) use ($dryRun, &$runs, &$jobs): void {
                foreach ($queues as $queue) {
                    $marked = $dryRun
                        ? $queue->jobs()->whereNull('status_code')->count()
                        : $queue->skipUnstartedJobs();

                    if ($marked > 0) {
                        $runs++;
                        $jobs += $marked;
                    }
                }
            });

        $this->info(sprintf(
            '%s %d job(s) of %d run(s).',
            $dryRun ? 'Would mark' : 'Marked',
            $jobs,
            $runs
        ));

        return self::SUCCESS;
    }

    /**
     * @param int[] $experiments
     */
    private function query(array $experiments): Builder
    {
        $statuses = [];

        foreach (Queue::UNSTARTED_JOBS_RUN_STATUSES as $status) {
            $statuses = array_merge($statuses, Queue::statusVariants($status));
        }

        return Queue::query()
            ->where('processed', 1)
            ->whereIn('status_code', $statuses)
            ->whereHas('jobs', fn (Builder $jobs) => $jobs->whereNull('status_code'))
            ->when([] !== $experiments, fn (Builder $query) => $query
                ->whereHas('crawler', fn (Builder $crawler) => $crawler->whereIn('experiment_id', $experiments)));
    }
}

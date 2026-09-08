<?php

use App\Modules\Job\Model\Job;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persist the decision function's verdict on the job row.
 *
 * Until now the verdict lived in exactly two places -- a line in the runner log
 * and the reports/lambda-jobdata-* object in MinIO -- and neither is joinable.
 * The one table every analysis does join on, jobs, said nothing at all about
 * whether the treatment a bot was configured for actually applied, which is why
 * 6543 archived decisions had never been read by anybody.
 *
 * docs/integration-contract.md 7.3 is the contract; orchestrator/status.py is
 * its executable copy (DECISION_FIELDS names these four columns).
 *
 * EVERY COLUMN IS NULLABLE AND NULL MEANS UNKNOWN. It never means clean and it
 * never means false. Three different jobs are indistinguishable through these
 * columns and all three are unknown:
 *
 * - every one of the ~31,500 jobs already in this table, which predate the
 *   migration;
 * - a job that never called the decision function -- a visit, a wait, a type
 *   job, i.e. most jobs;
 * - a lambdaselect job run by a runner that has not been redeployed yet, or
 *   against a decision function that emits no degradation report.
 *
 * A NOT NULL DEFAULT false would have asserted, at the moment it ran, that all
 * of them had applied their treatments -- a claim nobody measured, written into
 * the table everybody joins on. Nullable is also what makes the staged deploy
 * (API migration -> GUI -> runner jar) safe: every intermediate state produces
 * nulls, and nulls render as "no verdict".
 *
 * decision_selection_mode is 32 characters because it holds "llm" or "random".
 * It is a neutral descriptor and no consumer may branch on it alone, in SQL
 * either: a control crawler selecting at random is the healthy, expected case
 * and 82% of measured payloads.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table): void {
            // A *requested* filter failed to apply, or selection failed
            // outright. "A human should look at this decision."
            $table->boolean('decision_degraded')->nullable()->after('status_message');

            // The bot was configured to be treated and produced control-arm
            // data anyway. The alarm: this session's data is scientifically
            // void.
            $table->boolean('decision_treatment_lost')->nullable()->after('decision_degraded');

            // "llm" / "random". Description, not verdict.
            $table->string('decision_selection_mode', 32)->nullable()->after('decision_treatment_lost');

            // {"filters": {...}, "llm": {...}, "warnings": [...],
            //  "applied": [...], "requested": [...]}
            $table->json('decision_report')->nullable()->after('decision_selection_mode');

            // Both booleans are counted across the whole table by the rollups
            // in section 3.4 (jobs_degraded_count, jobs_treatment_lost_count,
            // latest_queues_treatment_lost), each of which is a correlated
            // subselect that would otherwise scan the jobs of every run on the
            // page.
            $table->index('decision_degraded');
            $table->index('decision_treatment_lost');
        });

        // The rollups ask Job::hasDecisionColumns() before they reference these
        // columns, and memoise the answer for the life of the process. php-fpm
        // gives every request a fresh one, so a web request cannot hold a stale
        // "no"; an artisan process that migrates and then keeps working can.
        Job::forgetDecisionColumnSupport();
    }

    /**
     * Drops the indexes before the columns.
     *
     * Losing the columns loses every verdict recorded since the deploy -- they
     * are written once, by the runner, on the status PUT, and nothing
     * regenerates them except decisions:backfill reading MinIO again. That is
     * recoverable, which is why this is a real down() rather than a refusal,
     * but it is not free.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table): void {
            $table->dropIndex(['decision_degraded']);
            $table->dropIndex(['decision_treatment_lost']);
        });

        Schema::table('jobs', function (Blueprint $table): void {
            $table->dropColumn([
                'decision_degraded',
                'decision_treatment_lost',
                'decision_selection_mode',
                'decision_report',
            ]);
        });

        Job::forgetDecisionColumnSupport();
    }
};

<?php

use App\Modules\Job\Model\Job;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persist WHICH video the decision function chose.
 *
 * The verdict columns added on 2026-09-07 say whether a treatment applied. They
 * do not say what the bot then watched, and neither does anything else that can
 * be queried: `decision_report` carries applied/filters/llm/requested/warnings,
 * and the chosen video lives only inside the reports/lambda-jobdata-* object in
 * MinIO. So "which videos has bot X watched?" -- close to the central question
 * of a personalisation study -- meant decoding thousands of zip objects.
 *
 * It is also the prerequisite for the `already_watched` filter, which has been
 * an empty TO-DO in lambda_function.py since the beginning and honestly reports
 * `not_implemented` on every invocation. That filter cannot be written at all
 * until a bot's history is answerable in one query.
 *
 * NULLABLE, and NULL MEANS UNKNOWN -- never "watched nothing". The same three
 * indistinguishable cases as the verdict columns: every job predating this
 * migration, every job that never called the decision function, and any
 * lambdaselect job whose payload could not be read. A default of '' would
 * assert that ~31,500 historical jobs chose no video, which is a claim nobody
 * measured.
 *
 * A YouTube video id is 11 characters today. The column is 64 so that a longer
 * id, or a different platform's, does not need a migration under time pressure.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table): void {
            $table->string('decision_selected_id', 64)
                ->nullable()
                ->after('decision_selection_mode');

            // Every real query is "what has this bot watched", which reaches
            // jobs through queues. The index is on the answer column so the
            // lookup does not become a full scan as the table grows.
            $table->index('decision_selected_id');
        });

        // A long-lived process that spans the migration -- an artisan command,
        // a queue worker -- has already memoised "absent". php-fpm does not
        // need this; the backfill run straight after a migrate does.
        Job::forgetDecisionColumnSupport();
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table): void {
            $table->dropIndex(['decision_selected_id']);
            $table->dropColumn('decision_selected_id');
        });

        Job::forgetDecisionColumnSupport();
    }
};

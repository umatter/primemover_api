<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index queues.status_code.
 *
 * jobs.status_code has been indexed since 2024 (add_indexes_to_jobs_table);
 * the queue side never was, and it is now read far harder than the job side.
 * Every crawler row serialises six latest_queues_* rollups as correlated count
 * subselects, three of which (succeeded, failed, warning) have the shape
 *
 *     select count(*) from queues
 *      where crawler_id = ? and started_at > ? and status_code in (...)
 *
 * so a 50-row page of /crawlers runs 150 of them, and without this index each
 * one falls back to the crawler_id foreign-key index and filters status_code by
 * scanning the matched rows. filter[status_code], filter[bad] and
 * sortBy=severity then read the same column across the whole table rather than
 * one crawler at a time.
 *
 * status_code is a plain nullable varchar holding a small vocabulary
 * (running/success/warning/failure/skipped plus the legacy spellings), which is
 * exactly the shape a single-column index serves well.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table): void {
            // Index for status filtering, the crawler rollup subselects and
            // severity sorting.
            $table->index('status_code');
        });
    }

    /**
     * Dropping an index is safe and reversible; the column and its data are
     * untouched, so a rollback costs nothing but the query plan.
     */
    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table): void {
            $table->dropIndex(['status_code']);
        });
    }
};

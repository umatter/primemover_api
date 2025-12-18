<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table): void {
            // Index for status filtering and sorting
            $table->index('status_code');

            // Index for date-based filtering (after, recent scopes)
            $table->index('created_at');

            // Composite index for common query pattern: filter by queue + sort by id
            $table->index(['queue_id', 'id'], 'jobs_queue_id_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table): void {
            $table->dropIndex(['status_code']);
            $table->dropIndex(['created_at']);
            $table->dropIndex('jobs_queue_id_id_index');
        });
    }
};

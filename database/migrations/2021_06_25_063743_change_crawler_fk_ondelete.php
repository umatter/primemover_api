<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('crawlers', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['experiment_id']);
            }

            $table->foreign('experiment_id')
                ->references('id')
                ->on('experiments')
                ->onDelete('cascade');
        });

        Schema::table('crawlers', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['user_id']);
            }

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('crawlers', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['proxy_id']);
            }

            $table->unsignedBigInteger('proxy_id')->nullable()->change();
            $table->foreign('proxy_id')
                ->references('id')
                ->on('proxies')
                ->nullOnDelete();
        });

        Schema::table('crawlers', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['agent_id']);
            }

            $table->unsignedBigInteger('agent_id')->nullable()->change();
            $table->foreign('agent_id')
                ->references('id')
                ->on('agents')
                ->nullOnDelete();
        });

        Schema::table('crawlers', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['configuration_id']);
            }

            $table->unsignedBigInteger('configuration_id')->nullable()->change();
            $table->foreign('configuration_id')
                ->references('id')
                ->on('configurations')
                ->nullOnDelete();
        });

        Schema::table('queues', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['crawler_id']);
            }

            $table->foreign('crawler_id')
                ->references('id')
                ->on('crawlers')
                ->onDelete('cascade');
        });

        Schema::table('jobs', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['queue_id']);
            }

            $table->foreign('queue_id')
                ->references('id')
                ->on('queues')
                ->onDelete('cascade');
        });
        Schema::table('behaviors', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['job_id']);
            }

            $table->foreign('job_id')
                ->references('id')
                ->on('jobs')
                ->onDelete('cascade');
        });
        Schema::table('params', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['configuration_id']);
            }

            $table->foreign('configuration_id')
                ->references('id')
                ->on('configurations')
                ->onDelete('cascade');
        });

        Schema::table('reports', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['job_id']);
            }

            $table->foreign('job_id')
                ->references('id')
                ->on('jobs')
                ->onDelete('cascade');
        });
    }

    public function down(): void {}
};

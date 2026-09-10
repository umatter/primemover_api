<?php

use App\Http\Controllers\API\AuthController;
use App\Modules\Agent\Http\Controller\Api\v1\AgentController;
use App\Modules\Agent\Http\Controller\Api\v1\RunnerAgentController;
use App\Modules\Behavior\Http\Controller\Api\v1\BehaviorController;
use App\Modules\Configuration\Http\Controller\Api\v1\ConfigurationController;
use App\Modules\Crawler\Http\Controller\Api\v1\CrawlerController;
use App\Modules\Experiment\Http\Controller\Api\v1\ExperimentController;
use App\Modules\Job\Http\Controller\Api\v1\JobController;
use App\Modules\Job\Http\Controller\Api\v1\RunnerJobController;
use App\Modules\Outlet\Http\Controller\Api\v1\OutletController;
use App\Modules\Param\Http\Controller\Api\v1\ParamController;
use App\Modules\Proxy\Http\Controller\Api\v1\ProxyController;
use App\Modules\Queue\Http\Controller\Api\v1\QueueController;
use App\Modules\Queue\Http\Controller\Api\v1\RunnerQueueController;
use App\Modules\Report\Http\Controller\Api\v1\ReportController;
use App\Modules\S3\Http\S3Controller;
use App\Modules\Term\Http\Controller\Api\v1\TermController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttleIp:1000,1')
    ->group(function (): void {
        Route::post('load-test', [CrawlerController::class, 'load']);

        Route::group(['middleware' => 'auth:api'], function (): void {
            // Truncates the database. Service accounts only.
            Route::middleware('can:administer-platform')
                ->get('dev/truncate-data', [\App\Http\Controllers\API\v1\Controller::class, 'truncateData']);

            // The runner accounts an experiment may be assigned to (runner_user_id).
            Route::get('runners', [\App\Http\Controllers\API\v1\RunnerController::class, 'index']);

            // Runner
            Route::prefix('runner')->group(function (): void {
                Route::put('agents/{agent}', [RunnerAgentController::class, 'update']);
                Route::put('jobs/{job}', [RunnerJobController::class, 'update']);
                Route::put('queues/{queue}', [RunnerQueueController::class, 'update']);
            });

            // Experiment
            Route::prefix('experiments')
                ->controller(QueueController::class)
                ->group(function (): void {
                    Route::get('{experiment}/queues-unreviewed', 'experimentUnreviewed');
                    Route::get('{experiment}/queues-reviewed', 'experimentReviewed');
                    Route::get('{experiment}/queues-processed', 'experimentProcessed');
                    Route::get('{experiment}/queues-unprocessed', 'experimentUnprocessed');
                });

            Route::prefix('experiments')
                ->controller(ExperimentController::class)
                ->group(function (): void {
                    Route::get('experiments-list', 'all');
                    // Triage above the bot: the six status buckets over a
                    // study's runs and jobs in a rolling window. Three
                    // segments, so it cannot collide with experiments/{id}.
                    Route::get('{experiment}/health', 'health');
                    Route::delete('{experiment}/destroy', 'forceDelete');
                    Route::post('upload', 'upload');
                    Route::middleware(['formdata'])->post('{experiment}/upload', 'upload');
                    Route::get('{experiment}/download', 'download');
                });

            // Queue
            Route::controller(QueueController::class)
                ->group(function (): void {
                    Route::get('queues/job/{jobid}', 'job');
                    Route::get('queues-processed', 'processed');
                    Route::get('queues-unprocessed', 'unprocessed');
                    Route::get('queues-reviewed', 'reviewed');
                    Route::get('queues-unreviewed', 'unreviewed');
                });

            // Crawler
            Route::controller(CrawlerController::class)
                ->group(function (): void {
                    Route::get('crawlers-processed', 'withProcessedQueues');
                    Route::get('crawlers-reviewed', 'withReviewedQueues');
                    Route::get('crawlers-unprocessed', 'withUnprocessedQueues');
                    Route::get('crawlers-inactive', 'inactive');
                    Route::delete('crawlers/{crawler}/destroy', 'forceDelete');
                    // The bot's watch history, for the already_watched filter.
                    Route::get('crawlers/{crawler}/watched', 'watched');
                    // Platform-wide destructive operations: service accounts only.
                    // disableProdCrawlers() deactivates EVERY user's production
                    // crawlers; undo/cleanup are equally global.
                    Route::middleware('can:administer-platform')->group(function (): void {
                        Route::get('crawlers-disable', 'disableProdCrawlers');
                        Route::delete('undo', 'undo');
                        Route::delete('cleanup', 'cleanup');
                    });
                    Route::get('crawlers-testing', 'testing');
                    Route::get('crawlers-protected', 'index');
                    Route::post('crawlers/{crawler}/upload', 'upload');
                });

            // Job
            // Before the apiResource block only for readability -- jobs/{job}/decision
            // has three segments and cannot collide with jobs/{job}.
            Route::get('jobs/{job}/decision', [JobController::class, 'decision']);

            // Files
            Route::get('file/{report}', [ReportController::class, 'getFile']);
            Route::post('upload', [S3Controller::class, 'store']);
            Route::get('download/{filename}', [S3Controller::class, 'download']);
            Route::get('download/report/{job}', [ReportController::class, 'download']);

            // Api Resources
            Route::apiResource('configurations', ConfigurationController::class);
            Route::apiResource('agents', AgentController::class);
            Route::apiResource('proxies', ProxyController::class);
            Route::apiResource('jobs', JobController::class);
            Route::apiResource('queues', QueueController::class);
            Route::apiResource('crawlers', CrawlerController::class);
            Route::apiResource('experiments', ExperimentController::class);
            Route::apiResource('behaviors', BehaviorController::class);
            Route::apiResource('reports', ReportController::class);
            Route::apiResource('terms', TermController::class);
            Route::apiResource('outlets', OutletController::class);
            Route::apiResource('params', ParamController::class);

        });

        Route::controller(AuthController::class)->group(function (): void {
            Route::post('login', 'login');
            Route::post('forgot-password', 'forgotPassword');
            Route::post('reset-password', 'resetPassword');

            Route::middleware(['oauth.token'])->group(function (): void {
                Route::post('auth/token', 'token');
            });

            Route::middleware(['auth:api'])->group(function (): void {
                Route::get('auth/user', 'user');
                Route::post('auth/change-password', 'changePassword');
            });
        });

        Route::get('ip/{ip?}', [CrawlerController::class, 'ip']);

    });

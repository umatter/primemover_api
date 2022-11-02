<?php

use App\Http\Controllers\API\AuthController;
use App\Modules\Agent\Http\Controller\Api\v1\AgentController;
use App\Modules\Behavior\Http\Controller\Api\v1\BehaviorController;
use App\Modules\Configuration\Http\Controller\Api\v1\ConfigurationController;
use App\Modules\Crawler\Http\Controller\Api\v1\CrawlerController;
use App\Modules\Experiment\Http\Controller\Api\v1\ExperimentController;
use App\Modules\Job\Http\Controller\Api\v1\JobController;
use App\Modules\Outlet\Http\Controller\Api\v1\OutletController;
use App\Modules\Param\Http\Controller\Api\v1\ParamController;
use App\Modules\Proxy\Http\Controller\Api\v1\ProxyController;
use App\Modules\Queue\Http\Controller\Api\v1\QueueController;
use App\Modules\Report\Http\Controller\Api\v1\ReportController;
use App\Modules\Term\Http\Controller\Api\v1\TermController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('load-test', [CrawlerController::class, 'load']);

    Route::group(['middleware' => 'auth:api'], function () {
        Route::resource('configurations', ConfigurationController::class);
        Route::resource('agents', AgentController::class);
        Route::resource('proxies', ProxyController::class);
        Route::resource('jobs', JobController::class);
        Route::resource('queues', QueueController::class);
        Route::resource('crawlers', CrawlerController::class);
        Route::resource('experiments', ExperimentController::class);
        Route::resource('behaviors', BehaviorController::class);
        Route::resource('reports', ReportController::class);
        Route::resource('terms', TermController::class);
        Route::resource('outlets', OutletController::class);
        Route::resource('params', ParamController::class);

        // Experiment
        Route::prefix('experiments')
            ->controller(QueueController::class)
            ->group(function () {
                Route::get('{experiment}/queues-unreviewed', 'experimentUnreviewed');
                Route::get('{experiment}/queues-reviewed', 'experimentReviewed');
                Route::get('{experiment}/queues-processed', 'experimentProcessed');
                Route::get('{experiment}/queues-unprocessed', 'experimentUnprocessed');
            });

        Route::prefix('experiments')
            ->controller(ExperimentController::class)
            ->group(function () {
                Route::get('experiments-list', 'all');
                Route::delete('{experiment}/destroy', 'forceDelete');
            });

        Route::controller(QueueController::class)
            ->group(function () {
                Route::get('queues/job/{jobid}', 'job');
                Route::get('queues-processed', 'processed');
                Route::get('queues-unprocessed', 'unprocessed');
                Route::get('queues-reviewed', 'reviewed');
                Route::get('queues-unreviewed', 'unreviewed');
            });

        Route::controller(CrawlerController::class)
            ->group(function () {
                Route::get('crawlers-processed', 'withProcessedQueues');
                Route::get('crawlers-reviewed', 'withReviewedQueues');
                Route::get('crawlers-unprocessed', 'withUnprocessedQueues');
                Route::get('crawlers-inactive', 'inactive');
                Route::delete('crawlers/{crawler}/destroy', 'forceDelete');
                Route::get('crawlers-disable', 'disableProdCrawlers');
                Route::get('crawlers-testing', 'testing');
                Route::get('crawlers-protected', 'index');
                Route::post('load', 'load');
                Route::delete('undo', 'undo');
                Route::delete('cleanup', 'cleanup');
            });

        Route::get('file/{report}', [ReportController::class, 'getFile']);
    });

    Route::post('login', [AuthController::class, 'login']);
    Route::get('ip/{ip?}', [CrawlerController::class, 'ip']);
    Route::post('auth/token', [AuthController::class, 'token']);
    Route::group(['middleware' => ['auth:api']], function () {
        Route::get('auth/user', [AuthController::class, 'user']);
    });
});

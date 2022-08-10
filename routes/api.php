<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('load-test', 'API\v1\CrawlerController@load');

    Route::group(['middleware' => 'auth:api'], function () {
        Route::resource('configurations', 'API\v1\ConfigurationController');
        Route::resource('agents', 'API\v1\AgentController');
        Route::resource('proxies', 'API\v1\ProxyController');
        Route::resource('jobs', 'API\v1\JobController');
        Route::resource('queues', 'API\v1\QueueController');
        Route::resource('crawlers', 'API\v1\CrawlerController');
        Route::resource('experiments', 'API\v1\ExperimentController');
        Route::resource('behaviors', 'API\v1\BehaviorController');
        Route::resource('reports', 'API\v1\ReportController');
        Route::resource('terms', 'API\v1\TermController');
        Route::resource('outlets', 'API\v1\OutletController');
        Route::resource('params', 'API\v1\ParamController');

        Route::get('queues/job/{jobid}/', 'API\v1\QueueController@job');
        Route::get('queues-processed', 'API\v1\QueueController@processed');
        Route::get('queues-unprocessed', 'API\v1\QueueController@unprocessed');
        Route::get('queues-reviewed', 'API\v1\QueueController@reviewed');
        Route::get('queues-unreviewed', 'API\v1\QueueController@unreviewed');
        Route::get('experiments/{experiment}/queues-unreviewed', 'API\v1\QueueController@experimentUnreviewed');
        Route::get('experiments/{experiment}/queues-reviewed', 'API\v1\QueueController@experimentReviewed');
        Route::get('experiments/{experiment}/queues-processed', 'API\v1\QueueController@experimentProcessed');
        Route::get('experiments/{experiment}/queues-unprocessed', 'API\v1\QueueController@experimentUnprocessed');
        Route::get('crawlers-processed', 'API\v1\CrawlerController@withProcessedQueues');
        Route::get('crawlers-reviewed', 'API\v1\CrawlerController@withReviewedQueues');
        Route::get('crawlers-unprocessed', 'API\v1\CrawlerController@withUnprocessedQueues');
        Route::get('crawlers-inactive', 'API\v1\CrawlerController@inactive');
        Route::delete('crawlers/{crawler}/destroy', 'API\v1\CrawlerController@forceDelete');
        Route::delete('experiments/{experiment}/destroy', 'API\v1\ExperimentController@forceDelete');
        Route::get('crawlers-disable', 'API\v1\CrawlerController@disableProdCrawlers');
        Route::get('file/{report}', 'API\v1\ReportController@getFile');
        Route::get('crawlers-testing', 'API\v1\CrawlerController@testing');

        Route::get('crawlers-protected', 'API\v1\CrawlerController@index');
        Route::post('load', 'API\v1\CrawlerController@load');

        Route::delete('undo', 'API\v1\CrawlerController@undo');
        Route::delete('cleanup', 'API\v1\CrawlerController@cleanup');
    });

    Route::group(['middleware' => 'guest:api'], function () {
        Route::post('login', 'API\AuthController@login');
        Route::get('ip/{ip?}', 'API\v1\CrawlerController@ip');
    });

    Route::post('auth/token', [AuthController::class, 'token']);

    Route::group(['middleware' => ['auth:api']], function () {
        Route::get('auth/user', [AuthController::class, 'user']);
    });
});

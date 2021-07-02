# Routes

## Regular resource routes

    Route::resource('configurations', 'API\v1\ConfigurationController');
    Route::resource('agents', 'API\v1\AgentController');
    Route::resource('proxies', 'API\v1\ProxyController');
    Route::resource('jobs', 'API\v1\JobController');
    Route::resource('queues', 'API\v1\QueueController');
    Route::resource('crawlers', 'API\v1\CrawlerController');
    Route::resource('experiments', 'API\v1\ExperimentController');
    Route::resource('posts', 'API\v1\PostController');
    Route::resource('behaviors', 'API\v1\BehaviorController');
    Route::resource('reports', 'API\v1\ReportController');
    Route::resource('terms', 'API\v1\TermController');
    Route::resource('outlets', 'API\v1\OutletController');
    Route::resource('params', 'API\v1\ParamController');

## Extra routes

    Route::get('queues/job/{jobid}/', 'API\v1\QueueController@job');
    Route::get('queues-processed', 'API\v1\QueueController@processed');
    Route::get('queues-unprocessed', 'API\v1\QueueController@unprocessed');
    Route::get('queues-reviewed', 'API\v1\QueueController@reviewed');
    Route::get('queues-unreviewed', 'API\v1\QueueController@unreviewed');
    Route::get('crawlers-processed', 'API\v1\CrawlerController@withProcessedQueues');
    Route::get('crawlers-reviewed', 'API\v1\CrawlerController@withReviewedQueues');
    Route::get('crawlers-unprocessed', 'API\v1\CrawlerController@withUnprocessedQueues');
    Route::get('crawlers-inactive', 'API\v1\CrawlerController@inactive');
    Route::get('crawlers-disable', 'API\v1\CrawlerController@disableProdCrawlers');
    Route::get('ip/{ip?}', 'API\v1\CrawlerController@ip');
    Route::get('file/{report}', 'API\v1\ReportController@getFile');
    Route::get('crawlers-testing', 'API\v1\CrawlerController@testing');
    Route::get('experiments/{experiment}/queues-unreviewed', 'API\v1\QueueController@experimentUnreviewed');
    Route::get('experiments/{experiment}/queues-reviewed', 'API\v1\QueueController@experimentReviewed');
    Route::get('experiments/{experiment}/queues-processed', 'API\v1\QueueController@experimentProcessed');
    Route::get('experiments/{experiment}/queues-unprocessed', 'API\v1\QueueController@experimentUnprocessed');

## Route methods based on Proxy example

Method | Route | Controller@method
---|---|---
POST | api/v1/proxies | App\Http\Controllers\API\v1\ProxyController@store                     
GET/HEAD | api/v1/proxies | App\Http\Controllers\API\v1\ProxyController@index                     
GET/HEAD | api/v1/proxies/create | App\Http\Controllers\API\v1\ProxyController@create                    
GET/HEAD | api/v1/proxies/{proxy} | App\Http\Controllers\API\v1\ProxyController@show                      
PUT/PATCH | api/v1/proxies/{proxy} | App\Http\Controllers\API\v1\ProxyController@update                    
DELETE | api/v1/proxies/{proxy} | App\Http\Controllers\API\v1\ProxyController@destroy                   
GET/HEAD | api/v1/proxies/{proxy}/edit | App\Http\Controllers\API\v1\ProxyController@edit                      

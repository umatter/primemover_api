<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/**
 * Prefix: /api/v1
 */
Route::prefix('v1')->group(function () {

    Route::post('load-test', 'API\v1\CrawlerController@load');


    /**
     * Not protected routes (for now?)
     */
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

    /**
     * Protected routes
     */
    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('crawlers-protected', 'API\v1\CrawlerController@index');

        Route::post('load', 'API\v1\CrawlerController@load');
        Route::delete('undo', 'API\v1\CrawlerController@undo');
        Route::delete('cleanup', 'API\v1\CrawlerController@cleanup');
    });

    Route::group(['middleware' => 'auth:api'], function () {
        //Route::post('logout', 'Auth\LoginController@logout');
        //Route::get('/user', 'Auth\UserController@current');

        Route::patch('settings/profile', 'Settings\ProfileController@update');
        Route::patch('settings/password', 'Settings\PasswordController@update');
    });

    Route::group(['middleware' => 'guest:api'], function () {

        //Route::post('register', 'API\AuthController@register');
        Route::post('login', 'API\AuthController@login');

        //Route::post('login', 'Auth\LoginController@login');
        //Route::post('register', 'Auth\RegisterController@register');

        Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');
        Route::post('password/reset', 'Auth\ResetPasswordController@reset');

        Route::post('email/verify/{user}', 'Auth\VerificationController@verify')->name('verification.verify');
        Route::post('email/resend', 'Auth\VerificationController@resend');

        Route::post('oauth/{driver}', 'Auth\OAuthController@redirectToProvider');
        Route::get('oauth/{driver}/callback', 'Auth\OAuthController@handleProviderCallback')->name('oauth.callback');

    });

});

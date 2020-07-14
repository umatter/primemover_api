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

Route::prefix('v1')->group(function () {

    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('logout', 'Auth\LoginController@logout');
        Route::get('/user', 'Auth\UserController@current');

        Route::resource('configurations', 'API\v1\ConfigurationController');
        Route::resource('agents', 'API\v1\AgentController');
        Route::resource('proxies', 'API\v1\ProxyController');
        Route::resource('jobs', 'API\v1\JobController');
        Route::resource('crawlers', 'API\v1\CrawlerController');
        Route::resource('posts', 'API\v1\PostController');
        Route::resource('behaviors', 'API\v1\BehaviorController');

        Route::patch('settings/profile', 'API\v1\Settings\ProfileController@update');
        Route::patch('settings/password', 'API\v1\Settings\PasswordController@update');
    });
});

Route::group(['middleware' => 'guest:api'], function () {
    Route::post('login', 'Auth\LoginController@login');
    Route::post('register', 'Auth\RegisterController@register');

    Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');
    Route::post('password/reset', 'Auth\ResetPasswordController@reset');

    Route::post('email/verify/{user}', 'Auth\VerificationController@verify')->name('verification.verify');
    Route::post('email/resend', 'Auth\VerificationController@resend');

    Route::post('oauth/{driver}', 'Auth\OAuthController@redirectToProvider');
    Route::get('oauth/{driver}/callback', 'Auth\OAuthController@handleProviderCallback')->name('oauth.callback');

});


<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => Config::get('api.version'), 'middleware' => ['post_social']], function () {
    Route::get(Config::get('api.endpoints.session_retrieval'), '\Modules\Session\App\Http\Controllers\SessionController@getMembership');
});

Route::group(['prefix' => Config::get('api.version'), 'middleware' => ['auth.session']], function () {
    Route::post(Config::get('api.endpoints.session_check_is_poster'), '\Modules\Session\App\Http\Controllers\SessionController@isPoster');
});

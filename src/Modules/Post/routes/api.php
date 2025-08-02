<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Modules\Post\app\Http\Controllers\PostController;
use Modules\Post\app\Http\Controllers\PostSocialController;

// For user's Social
// for only posters
Route::group(['prefix' => Config::get('api.version'), 'middleware' => ['auth.session', 'role']], function () {
    Route::resource(Config::get('api.endpoints.posts'), PostController::class, ['only' => ['index', 'show', 'store', 'update', 'destroy']]);
});

Route::group(['prefix' => Config::get('api.version'), 'middleware' => ['post_social']], function () {
    Route::resource(Config::get('api.endpoints.posts_social'), PostSocialController::class, ['only' => ['index']]);
});

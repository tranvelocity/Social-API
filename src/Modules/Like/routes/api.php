<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Modules\Like\app\Http\Controllers\LikeController;

Route::group(['prefix' => Config::get('api.version'), 'middleware' => ['auth.session', 'role']], function () {
    Route::resource(Config::get('api.endpoints.post_likes'), LikeController::class, ['only' => ['index']]);
    Route::post(Config::get('api.endpoints.post_like'), '\Modules\Like\app\Http\Controllers\LikeController@like');
    Route::post(Config::get('api.endpoints.post_unlike'), '\Modules\Like\app\Http\Controllers\LikeController@unlike');
});

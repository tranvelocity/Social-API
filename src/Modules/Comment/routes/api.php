<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Modules\Comment\app\Http\Controllers\CommentController;

Route::group(['prefix' => Config::get('api.version'), 'middleware' => ['auth.session', 'role']], function () {
    Route::resource(Config::get('api.endpoints.post_comments'), CommentController::class, ['only' => ['index', 'show', 'store', 'update', 'destroy']]);

    Route::post(Config::get('api.endpoints.post_comment_publish'), '\Modules\Comment\app\Http\Controllers\CommentController@publishComment');
    Route::post(Config::get('api.endpoints.post_comment_unpublish'), '\Modules\Comment\app\Http\Controllers\CommentController@unpublishComment');
});

<?php

use Modules\PingPong\Http\Controllers\Api\v1\PingPongController as PingPongV1Controller;
use Modules\PingPong\Http\Controllers\Api\V2\PingPongController as PingPongV2Controller;
use Illuminate\Support\Facades\Route;

Route::middleware('hmac.auth')->group(function (): void {
    Route::group(['prefix' => 'v1'], function () {
        Route::resource('ping-pong', PingPongV1Controller::class, ['only' => ['index', 'show', 'store', 'update', 'destroy']]);
    });

    Route::group(['prefix' => 'v2'], function () {
        Route::resource('ping-pong', PingPongV2Controller::class, ['only' => ['index', 'show', 'store', 'update', 'destroy']]);
    });
});

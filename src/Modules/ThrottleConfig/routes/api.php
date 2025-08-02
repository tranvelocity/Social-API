<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Modules\ThrottleConfig\app\Http\Controllers\ThrottleConfigController;

Route::group(['prefix' => Config::get('api.version'), 'middleware' => ['auth.session']], function () {
    Route::resource(Config::get('api.endpoints.throttle_config'), ThrottleConfigController::class, ['only' => ['index', 'store', 'destroy']]);
});

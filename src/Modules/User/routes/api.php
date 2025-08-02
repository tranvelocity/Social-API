<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Modules\User\app\Http\Controllers\UserController;

Route::group(['prefix' => Config::get('api.version')], function () {
    Route::resource(Config::get('api.endpoints.users'), UserController::class, ['only' => ['index', 'show', 'store', 'update', 'destroy']]);
});

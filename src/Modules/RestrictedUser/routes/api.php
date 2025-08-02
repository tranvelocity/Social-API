<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Modules\RestrictedUser\app\Http\Controllers\RestrictedUserController;

Route::group(['prefix' => Config::get('api.version'), 'middleware' => ['auth.session']], function () {
    Route::resource(Config::get('api.endpoints.restricted_users'), RestrictedUserController::class, ['only' => ['index', 'show', 'store', 'update', 'destroy']]);
});

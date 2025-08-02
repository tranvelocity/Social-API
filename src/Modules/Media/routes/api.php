<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Modules\Media\app\Http\Controllers\MediaController;

Route::group(['prefix' => Config::get('api.version'), 'middleware' => ['auth.session', 'role']], function () {
    Route::resource(Config::get('api.endpoints.medias'), MediaController::class, ['only' => ['index', 'show', 'store', 'update', 'destroy']]);
});

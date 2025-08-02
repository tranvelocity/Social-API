<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Modules\Poster\app\Http\Controllers\PosterController;

Route::group(['prefix' => Config::get('api.version')], function () {
    Route::resource(Config::get('api.endpoints.posters'), PosterController::class, ['only' => ['index', 'show', 'store', 'update', 'destroy']]);
});

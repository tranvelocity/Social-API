<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => Config::get('api.version')], function () {
    Route::delete(Config::get('api.endpoints.clear_member_caches'), '\Modules\Cache\app\Http\Controllers\CacheController@destroyCachedMemberData');
    Route::delete(Config::get('api.endpoints.clear_ng_word_caches'), '\Modules\Cache\app\Http\Controllers\CacheController@destroyCachedNGWord');
});

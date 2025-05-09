<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

Route::group([
    'namespace'  => 'Luminix\Bi\Http\Controllers\Apis',
    'as'         => 'bi.api',
    'prefix'     => Config::get('luminix.bi.path', 'bi') . '-apis',
    'middleware' => Config::get('luminix.bi.middleware', ['web', 'auth', 'can:viewBi'])
], function () {

    Route::get('/dashboards', 'DashboardController@getDashboards');
    Route::get('/{dashboard}/widgets', 'DashboardController@getWidgets');

    Route::get('/{dashboard}/widgets/{widget}', 'WidgetController@getWidget');
    Route::get('/{dashboard}/widgets/{widget}/csv', 'WidgetController@download');
    Route::get('/{dashboard}/filters/{filter}', 'FilterController@getFilter');

    // Novas rotas para criação dinâmica de widgets
    Route::post('/widgets/create', 'DashboardController@createWidget');
    Route::post('/widgets/load-from-db', 'DashboardController@loadWidgetsFromDb');
});

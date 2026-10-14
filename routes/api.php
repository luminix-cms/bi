<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

Route::group([
    'namespace'  => 'Luminix\Bi\Http\Controllers\Apis',
    'as'         => 'bi.api.',
    'prefix'     => Config::get('luminix.bi.path', 'bi') . '-apis',
    'middleware' => Config::get('luminix.bi.middleware', ['web', 'auth', 'can:read-bi-reports'])
], function () {

    Route::get('/dashboards', 'DashboardController@getDashboards')
        ->name('dashboards');
    Route::get('/{dashboard}/widgets', 'DashboardController@getWidgets')
        ->name('dashboard.widgets');

    Route::get('/{dashboard}/widgets/{widget}', 'WidgetController@getWidget')
        ->name('dashboard.widgets.widget');
    Route::get('/{dashboard}/widgets/{widget}/csv', 'WidgetController@download')
        ->name('dashboard.widgets.widget.csv');
    Route::get('/{dashboard}/filters/{filter}', 'FilterController@getFilter')
        ->name('dashboard.filters.filter');

});

<?php

Route::group([
    'namespace'  => 'Luminix\Bi\Http\Controllers',
    'as'         => 'bi',
    'prefix'     => Config::get('luminix.bi.path', 'bi'),
    'middleware' => Config::get('luminix.bi.middleware', ['web', 'auth', 'can:viewBi'])
], function () {

    Route::get('/', 'HomepageController@getIndex');
    Route::get('/{dashboard}', 'HomepageController@getDashboard')
        ->name('dashboard');

});

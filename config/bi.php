<?php

/*
|--------------------------------------------------------------------------
| Bi Configuration
|--------------------------------------------------------------------------
|
| This is the configuration file for `luminix/bi` package.
|
*/
return [

    /*
    |--------------------------------------------------------------------------
    | Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where the package will be accessible from. Feel free
    | to change this path to anything you like. The apis will have `-apis` appended
    | to this path.
    |
    */
    'path'       => env('LUMINIX_BI_PATH', 'bi'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | These are the middlewares that will be applied to the package routes.
    |
    */
    'middleware' => ['web', 'auth', 'can:viewBi'],

    /*
    |--------------------------------------------------------------------------
    | Debug
    |--------------------------------------------------------------------------
    |
    | This setting controls whether the package will display debug information
    | in the api responses. If set to `true`, any queries that are run will be
    | displayed in the response.
    |
    */
    'debug'      => env('LUMINIX_BI_DEBUG', false),

];

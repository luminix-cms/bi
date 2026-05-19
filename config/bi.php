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
    'middleware' => ['web', 'auth', 'can:read-bi-reports'],

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection used for all BI queries. Useful in master/slave
    | setups where reads should go to a replica. When null, the model's default
    | connection is used (DB_CONNECTION).
    |
    */
    'connection' => env('BI_DB_CONNECTION', null),

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

<?php

    Route::get('/accounts', 'AccountController@index');

    Route::group([
        'prefix' => 'account'
    ], function()
    {
        Route::group([
            'prefix' => '{id}'
        ], function()
        {
            Route::put('/status', 'AccountController@updateStatus');
        });
    });

?>
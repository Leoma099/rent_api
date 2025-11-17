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
            Route::put('', 'AccountController@update');
            Route::put('/status', 'AccountController@updateStatus');
            Route::delete('', 'AccountController@destroy');
        });
    });

?>
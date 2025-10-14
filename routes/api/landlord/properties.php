<?php

    Route::get('properties', 'PropertyController@index');
    
    Route::group([
        'prefix' => 'property',
    ], function()
    {
        Route::group([
            'prefix' => '{id}'
        ], function()
        {
            Route::get('', 'PropertyController@show');

            Route::get  ('edit', 'PropertyController@update');
        });
    });
?>
<?php

    Route::get('properties', 'PropertyController@index');

    Route::group([
        'prefix' => 'property'
    ], function()
    {
        Route::group([
            'prefix' => '{id}'
        ], function()
        {
            Route::delete('', 'PropertyController@destroy');
            Route::get('', 'PropertyController@show');
        },);
    })

?>
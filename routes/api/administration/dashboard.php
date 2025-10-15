<?php

    Route::group([
        'prefix' => 'dashboard'
    ], function()
    {
        Route::get('summary/properties', 'DashboardController@totalProperties');
        Route::get('summary/total/properties', 'DashboardController@pendingProperty');
        Route::get('summary/user/landlords', 'DashboardController@landlordCount');
        Route::get('summary/user/tenants', 'DashboardController@tenantCount');
        Route::get('summary/users', 'DashboardController@pendingCount');

        Route::get('properties/options', 'DashboardController@propertyOptions');
    });

?>
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MyAccountController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\NearbyController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\InquiryMessageController;
use App\Http\Controllers\ScheduleDateTimeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
// GUEST CAN VIEW
Route::get('/commercialhub/properties', [PropertyController::class, 'index']);
Route::get('/commercialhub/properties/{id}', [PropertyController::class, 'show']);
Route::post('/register', [AccountController::class, 'register']);
Route::get('/nearby', [NearbyController::class, 'index']);
Route::get('/featured-properties', 'PropertyController@featured');
Route::get('/recent-properties', 'PropertyController@recentProperty');
Route::get('/properties/{id}/recommended', 'PropertyController@recommended');

Route::post('/forgot-password', 'ForgotPasswordController@sendResetLink');
Route::post('/reset-password', 'ForgotPasswordController@resetPassword');

Route::group([
    'namespace'  => 'API\Administration',
    'prefix'     => 'admin',
    'middleware' => ['auth:sanctum'],
], function () {
    require __DIR__.'/api/administration/dashboard.php';
    require __DIR__.'/api/administration/accounts.php';
    require __DIR__.'/api/administration/properties.php';
});

Route::middleware('auth:sanctum')->group(function ()
{
    // PROPERTIES
    // BOOKING SCHEDULES
    // LEASES
    // SCHEDULES
    // INQUIRES
    // ACCOUNTS
    // USERS
    // NOTIFICATIONS

    Route::post('logout','AuthController@logout');

    Route::get('/notifications' , 'NotificationController@index');
    Route::post('/notifications/read-all' , 'NotificationController@markAllAsRead');
    Route::post('/notifications/{id}/read' , 'NotificationController@markAsRead');
    Route::delete('/notifications/{id}' , 'NotificationController@destroy');
    Route::delete('/notificationAll' , 'NotificationController@destroyAll');

    Route::get('/properties', [PropertyController::class, 'index']);
    Route::post('/properties', [PropertyController::class, 'store']);
    Route::put('/properties/{id}', [PropertyController::class, 'update']);
    Route::get('/properties/{id}', [PropertyController::class, 'show']);
    Route::get('/totalProperties', [PropertyController::class, 'totalProperties']);
    Route::get('/schedules', [ScheduleDateTimeController::class, 'index']);
    Route::post('/schedules', [ScheduleDateTimeController::class, 'store']);

    Route::put('/status-properties/{id}', 'PropertyController@updateStatus');
    Route::put('/featured-properties/{id}', 'PropertyController@updateFeatured');
    Route::delete('/properties/{id}', 'PropertyController@destroy');

    Route::put('/properties/{id}/approved', [PropertyController::class, 'approved']);
    Route::get('/totalPendingProperties', 'PropertyController@pendingProperty');

    

    Route::get('/accounts', [AccountController::class, 'index']);
    Route::get('/accounts/{id}', [AccountController::class, 'show']);
    Route::post('/accounts', [AccountController::class, 'store']);
    Route::put('/accounts/{id}', [AccountController::class, 'update']);
    Route::delete('/accounts/{id}', [AccountController::class, 'destroy']);
    Route::get('/accountClient', [AccountController::class, 'clientDataInfo']);

    Route::put('/accountUpdate', [MyAccountController::class, 'update']);
    Route::put('/accounts/{id}', 'AccountController@updateStatus');
    Route::delete('/accounts/{id}', 'AccountController@destroy');

    Route::get('/user', [UserController::class, 'index']);
    Route::get('/user/{id}', [UserController::class, 'show']);
    Route::post('/user', [UserController::class, 'store']);
    Route::put('/user/{id}', [UserController::class, 'update']);

    ROute::get('/totalUserPending', 'UserController@pendingCount');  

    Route::get('/totalLandlords', 'UserController@landlordCount');
    Route::get('/totalTenants', 'UserController@tenantCount');

    // SCHEDULE
    Route::get('/schedules', 'ScheduleController@index');
    Route::post('/schedules', 'ScheduleController@store');

    // LANDMARK
    Route::get('/landmarks', 'LandmarkController@index');
    Route::post('/landmarks', 'LandmarkController@store');

    // INQUIRIES
    Route::get('/inquiries', [InquiryController::class, 'index']);
    Route::post('/inquiries', [InquiryController::class, 'store']);

    // INQUIRY MESSAGES
    Route::get('/inquiries/{inquiry}/messages', [InquiryMessageController::class, 'index']);
    Route::post('/inquiries/{inquiry}/messages', [InquiryMessageController::class, 'store']);

    // BOOKING
    Route::get('/bookings', 'BookingScheduleController@index');
    Route::post('/bookings', 'BookingScheduleController@store');
    Route::put('/bookings/{id}', 'BookingScheduleController@updateStatus');
    Route::delete('/bookings/{id}', 'BookingScheduleController@destroy');

    // SAVED PROPERTY
    Route::get('/saved-properties', 'SavedPropertyController@index');
    Route::post('/saved-properties', 'SavedPropertyController@store');
    Route::delete('/saved-properties/{id}', 'SavedPropertyController@destroy');

    // LEASES
    Route::get('/leases', 'LeaseController@index');
    Route::post('/leases', 'LeaseController@store');
    Route::put('/leases/{id}', 'LeaseController@updateStatus');
    Route::delete('/leases/{id}', 'LeaseController@destroy');
    Route::get('/leaseCount', 'LeaseController@totalLease');

    // LANDLORD DASHBOARD
    Route::get('/pendingCount', 'LandlordDashboardController@totalPending');
    Route::get('/bookedCount', 'LandlordDashboardController@totalBooked');
    Route::get('/inquireCount', 'LandlordDashboardController@totalInquire');



});

// Login route (fixed with name)
Route::post('login', 'AuthController@login');

Route::get('test/users', function()
{
    return \App\Models\Account::all();
});
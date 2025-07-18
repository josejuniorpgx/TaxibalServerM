<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers;
use App\Http\Controllers\API;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register',[API\UserController::class, 'register']);
Route::post('driver-register',[API\UserController::class, 'driverRegister']);
Route::post('login',[API\UserController::class,'login']);
Route::post('forget-password',[ API\UserController::class,'forgetPassword']);
Route::post('social-login',[ API\UserController::class, 'socialLogin' ]);
Route::get('user-list',[API\UserController::class, 'userList']);
Route::get('user-detail',[API\UserController::class, 'userDetail']);

Route::get('document-list', [ API\DocumentController::class, 'getList' ] );

Route::get('service-list', [ API\ServiceController::class, 'getList' ]);
Route::post('estimate-price-time', [ API\ServiceController::class, 'estimatePriceTime' ]);
Route::get('appsetting', [ API\DashboardController::class, 'appsetting'] );
Route::get('near-by-driver',[ App\Http\Controllers\HomeController::class, 'driverListMap' ]);
Route::get('language-table-list', [API\LanguageTableController::class, 'getList']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('driver-document-list', [ API\DriverDocumentController::class, 'getList' ] );
    Route::post('driver-document-save', [ App\Http\Controllers\DriverDocumentController::class, 'store' ] );
    Route::post('driver-document-update/{id}', [ App\Http\Controllers\DriverDocumentController::class, 'update' ] );
    Route::post('driver-document-delete/{id}', [ App\Http\Controllers\DriverDocumentController::class, 'destroy' ] );

    Route::post('verify-coupon', [ API\RideRequestController::class, 'verifyCoupon'] );
    Route::post('save-riderequest', [ App\Http\Controllers\RideRequestController::class, 'store'] );
    Route::post('riderequest-update/{id}', [ App\Http\Controllers\RideRequestController::class, 'update' ] );
    Route::get('riderequest-list', [ API\RideRequestController::class, 'getList'] );
    Route::get('riderequest-detail', [ API\RideRequestController::class, 'getDetail'] );
    Route::post('riderequest-delete/{id}', [ App\Http\Controllers\RideRequestController::class, 'destroy' ] );
    Route::get('coupon-list', [ API\CouponController::class, 'getList'] );

    Route::post('apply-bid', [ App\Http\Controllers\RideRequestController::class, 'applyBidRideRequest'] );
    Route::post('get-bidding-riderequest', [ App\Http\Controllers\RideRequestController::class, 'getBiddingDrivers'] );
    Route::post('riderequest-bid-respond', [ App\Http\Controllers\RideRequestController::class, 'acceptBidRequest'] );

    Route::post('riderequest/{id}/drop/{index}', [App\Http\Controllers\RideRequestController::class, 'updateDropLocationTime']);
    Route::post('riderequest-respond', [ App\Http\Controllers\RideRequestController::class, 'acceptRideRequest' ] );
    Route::post('complete-riderequest', [ API\RideRequestController::class, 'completeRideRequest' ] );

    Route::get('wallet-detail', [ API\WalletController::class, 'getWallatDetail'] );
    Route::post('save-wallet', [ API\WalletController::class, 'saveWallet'] );
    Route::get('wallet-list', [ API\WalletController::class, 'getList'] );
    Route::post('notification-list', [ API\NotificationController::class, 'getList'] );

    Route::get('payment-gateway-list', [ API\PaymentGatewayController::class, 'getList'] );

    Route::get('sos-list', [ API\SosController::class, 'getList'] );
    Route::post('save-sos', [ App\Http\Controllers\SosController::class, 'store'] );
    Route::post('sos-update/{id}', [ App\Http\Controllers\SosController::class, 'update' ] );
    Route::post('sos-delete/{id}', [ App\Http\Controllers\SosController::class, 'destroy'] );
    Route::post('admin-sos-notify', [ API\SosController::class, 'adminSosNotify'] );

    Route::post('save-ride-rating', [ API\RideRequestController::class, 'rideRating'] );
    
    Route::post('save-payment', [ API\PaymentController::class, 'paymentSave'] );

    Route::get('withdrawrequest-list', [ API\WithdrawRequestController::class, 'getList'] );
    Route::post('save-withdrawrequest', [ App\Http\Controllers\WithdrawRequestController::class, 'store'] );
    Route::post('update-status/{id}', [ App\Http\Controllers\WithdrawRequestController::class, 'updateStatus' ] );

    Route::post('save-complaint', [ App\Http\Controllers\ComplaintController::class, 'store'] );
    Route::post('update-complaint/{id}', [ App\Http\Controllers\ComplaintController::class, 'update'] );

    Route::get('complaintcomment-list', [ API\ComplaintCommentController::class, 'getList'] );
    Route::post('save-complaintcomment', [ App\Http\Controllers\ComplaintCommentController::class, 'store'] );
    Route::post('update-complaintcomment/{id}', [ App\Http\Controllers\ComplaintCommentController::class, 'update' ] );

    Route::get('admin-dashboard', [ API\DashboardController::class, 'adminDashboard'] );
    Route::get('rider-dashboard', [ API\DashboardController::class, 'riderDashboard'] );

    Route::get('current-riderequest', [ API\DashboardController::class, 'currentRideRequest'] );

    Route::post('earning-list', [ API\PaymentController::class, 'DriverEarningList'] );
    
    Route::post('update-profile', [ API\UserController::class, 'updateProfile']);
    Route::post('change-password',[ API\UserController::class, 'changePassword']);
    Route::post('update-user-status', [ API\UserController::class, 'updateUserStatus']);
    
    Route::post('delete-user-account', [ API\UserController::class, 'deleteUserAccount']);

    Route::get('additional-fees-list', [ API\AdditionalFeesController::class, 'getList'] );
    Route::get('logout',[ API\UserController::class, 'logout']);

    Route::post('setting-upload-image', [App\Http\Controllers\SettingController::class, 'settingUploadImage']);
    Route::post('save-setting', [ App\Http\Controllers\SettingController::class, 'settingUpdate'] );
    Route::get('get-setting', [API\SettingController::class, 'getList']);
    Route::get('frontenddata-list',[API\FrontendDataController::class,'getList']);
    Route::post('frontenddata-save',[ App\Http\Controllers\FrontendDataController::class, 'store' ]);
    Route::get('get-appsetting', [API\UserController::class, 'getAppSetting']);
    Route::post('update-appsetting', [App\Http\Controllers\SettingController::class, 'updateAppSetting']);

    //customer support route
    // Route::get('customersupport-list', [API\CustomerSupportController::class, 'getList']);
    // Route::post('customersupport-save', [App\Http\Controllers\CustomerSupportController::class, 'store']);
    // Route::post('customersupport-delete/{id}', [App\Http\Controllers\CustomerSupportController::class, 'destroy']);
    // Route::post('chatmessage-save', [App\Http\Controllers\CustomerSupportController::class, 'chatMessage']);
    // Route::post('status-save/{id}', [App\Http\Controllers\CustomerSupportController::class, 'updateStatus']);
});

Route::get('place-autocomplete-api', [ API\RideRequestController::class, 'placeAutoComplete' ] );
Route::get('place-detail-api', [ API\RideRequestController::class, 'placeDetail' ] );

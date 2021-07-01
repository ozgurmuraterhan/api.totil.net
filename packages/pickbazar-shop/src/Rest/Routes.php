<?php

use Illuminate\Support\Facades\Route;

use PickBazar\Http\Controllers\AddressController;
use PickBazar\Http\Controllers\AttributeController;
use PickBazar\Http\Controllers\AttributeValueController;
use PickBazar\Http\Controllers\ProductController;
use PickBazar\Http\Controllers\ProfileController;
use PickBazar\Http\Controllers\SettingsController;
use PickBazar\Http\Controllers\UserController;
use PickBazar\Http\Controllers\TypeController;
use PickBazar\Http\Controllers\OrderController;
use PickBazar\Http\Controllers\OrderStatusController;
use PickBazar\Http\Controllers\CategoryController;
use PickBazar\Http\Controllers\CouponController;
use PickBazar\Http\Controllers\AttachmentController;
use PickBazar\Http\Controllers\ShippingController;
use PickBazar\Http\Controllers\TaxController;
use PickBazar\Enums\Permission;



Route::post('/register', 'PickBazar\Http\Controllers\UserController@register');
Route::post('/token', 'PickBazar\Http\Controllers\UserController@token');
Route::post('/forget-password', 'PickBazar\Http\Controllers\UserController@forgetPassword');
Route::post('/verify-forget-password-token', 'PickBazar\Http\Controllers\UserController@verifyForgetPasswordToken');
Route::post('/reset-password', 'PickBazar\Http\Controllers\UserController@resetPassword');
Route::post('/contact', 'PickBazar\Http\Controllers\UserController@contactAdmin');
Route::post('/social-login-token', 'PickBazar\Http\Controllers\UserController@socialLogin');

Route::apiResource('products', ProductController::class, [
    'only' => ['index', 'show']
]);
Route::apiResource('types', TypeController::class, [
    'only' => ['index', 'show']
]);
Route::apiResource('attachments', AttachmentController::class, [
    'only' => ['index', 'show']
]);
Route::apiResource('categories', CategoryController::class, [
    'only' => ['index', 'show']
]);

Route::get('fetch-parent-category', 'PickBazar\Http\Controllers\CategoryController@fetchOnlyParent');

Route::apiResource('coupons', CouponController::class, [
    'only' => ['index', 'show']
]);

Route::post('coupons/verify', 'PickBazar\Http\Controllers\CouponController@verify');


Route::apiResource('order_status', OrderStatusController::class, [
    'only' => ['index', 'show']
]);

Route::apiResource('attributes', AttributeController::class, [
    'only' => ['index', 'show']
]);

Route::apiResource('attribute-values', AttributeValueController::class, [
    'only' => ['index', 'show']
]);

Route::apiResource('settings', SettingsController::class, [
    'only' => ['index']
]);


Route::group(['middleware' => ['can:' . Permission::CUSTOMER, 'auth:sanctum']], function () {
    Route::post('/logout', 'PickBazar\Http\Controllers\UserController@logout');
    Route::apiResource('orders', OrderController::class, [
        'only' => ['index', 'show', 'store']
    ]);
    Route::apiResource('attachments', AttachmentController::class, [
        'only' => ['store', 'update', 'destroy']
    ]);
    Route::post('checkout/verify', 'PickBazar\Http\Controllers\CheckoutController@verify');
    Route::get('me', 'PickBazar\Http\Controllers\UserController@me');
    Route::put('users/{id}', 'PickBazar\Http\Controllers\UserController@update');
    Route::post('/change-password', 'PickBazar\Http\Controllers\UserController@changePassword');
    Route::apiResource('address', AddressController::class, [
        'only' => ['destroy']
    ]);
});




Route::group(['middleware' => ['permission:' . Permission::SUPER_ADMIN, 'auth:sanctum']], function () {
    Route::apiResource('orders', OrderController::class, [
        'only' => ['update', 'destroy']
    ]);
    Route::apiResource('products', ProductController::class, [
        'only' => ['store', 'update', 'destroy']
    ]);
    Route::apiResource('types', TypeController::class, [
        'only' => ['store', 'update', 'destroy']
    ]);
    Route::apiResource('categories', CategoryController::class, [
        'only' => ['store', 'update', 'destroy']
    ]);
    Route::apiResource('coupons', CouponController::class, [
        'only' => ['store', 'update', 'destroy']
    ]);
    Route::apiResource('order_status', OrderStatusController::class, [
        'only' => ['store', 'update', 'destroy']
    ]);
    Route::apiResource('attributes', AttributeController::class, [
        'only' => ['store', 'update', 'destroy']
    ]);
    Route::apiResource('attribute-values', AttributeValueController::class, [
        'only' => ['store', 'update', 'destroy']
    ]);
    Route::apiResource('settings', SettingsController::class, [
        'only' => ['store']
    ]);
    Route::apiResource('users', UserController::class);
    Route::post('users/ban-user', 'PickBazar\Http\Controllers\UserController@banUser');
    Route::post('users/active-user', 'PickBazar\Http\Controllers\UserController@activeUser');

    Route::get('analytics', 'PickBazar\Http\Controllers\AnalyticsController@analytics');
    Route::apiResource('taxes', TaxController::class);
    Route::apiResource('shipping', ShippingController::class);
    Route::get('popular-products', 'PickBazar\Http\Controllers\AnalyticsController@popularProducts');
});

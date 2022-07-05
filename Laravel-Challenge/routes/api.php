<?php

use Illuminate\Support\Facades\Route;
/**
 * Register
 */
Route::post('v1/register', 'Api\RegistersController@index');
/**
 * Google (Android) & Apple (IOS) API Mock
 */
Route::post('v1/google', 'Api\RegistersController@indexDevices');
Route::post('v1/ios', 'Api\RegistersController@indexDevices');
/**
 * Client token parameter required
 */
Route::group(['middleware' => ['authClientToken']], function () {
    /**
     * Two method purchase
     */
    Route::match(['get', 'post'], 'v1/purchase', 'Api\PurchaseSController@index');
    /**
     * Get purchase list & active subscription list
     */
    Route::get('v1/get-subscriptions', 'Api\PurchaseSController@getSubscriptions');
});

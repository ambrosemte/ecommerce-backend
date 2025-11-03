<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DeliveryDetailController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SpecificationController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WishlistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['prefix' => 'v1'], function () {
    //AUTH
    Route::group(['prefix' => 'auth'], function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('login-via-google', [AuthController::class, 'loginViaGoogle']);
        Route::post('login-via-facebook', [AuthController::class, 'loginViaFacebook']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('logout', [AuthController::class, 'logout']);

        Route::post('seller/login', [AuthController::class, 'login']);
        Route::post('seller/login-via-google', [AuthController::class, 'loginViaGoogle']);
        Route::post('seller/login-via-facebook', [AuthController::class, 'loginViaFacebook']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('logout', [AuthController::class, 'logout']);


        Route::post('login', [AuthController::class, 'login']);
        Route::post('login-via-google', [AuthController::class, 'loginViaGoogle']);
        Route::post('login-via-facebook', [AuthController::class, 'loginViaFacebook']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    //USER
    Route::group(['prefix' => 'user', 'middleware' => ['auth:sanctum']], function () {
        Route::get('profile', [UserController::class, 'getProfile']);
        Route::post('profile/image/update', [UserController::class, 'updateProfileImage']);
        Route::get('check-authentication', [UserController::class, 'checkAuthentication']);
        Route::put('profile/update', [UserController::class, 'updateProfile']);
        Route::patch('preferred-currency', [UserController::class, 'setPreferredCurrency']);
    });

    //STORE
    Route::group(['prefix' => 'store', 'middleware' => ['auth:sanctum']], function () {
        Route::get('seller/', [StoreController::class, 'index'])->middleware('role:seller|agent');
        Route::get('{id}', [StoreController::class, 'show'])->withoutMiddleware('auth:sanctum');
        Route::post('seller/create', [StoreController::class, 'store'])->middleware('role:seller|agent');
        Route::post('follow/{storeId}', [StoreController::class, 'follow']);
        Route::delete('seller/delete/{id}', [StoreController::class, 'delete'])->middleware('role:seller|agent');
    });

    //PRODUCT
    Route::group(['prefix' => 'product', 'middleware' => ['auth:sanctum']], function () {
        Route::get('featured-products', [ProductController::class, 'featuredProducts'])->withoutMiddleware('auth:sanctum');
        Route::get('search', [ProductController::class, 'search'])->withoutMiddleware('auth:sanctum');
        Route::get('{id}', [ProductController::class, 'show'])->withoutMiddleware('auth:sanctum');
        Route::post('seller/create', [ProductController::class, 'store'])->middleware('role:seller|agent');
        Route::delete('seller/{id}', [ProductController::class, 'delete'])->middleware('role:seller|agent');
    });

    //CATEGORY
    Route::group(['prefix' => 'category', 'middleware' => ['auth:sanctum']], function () {
        Route::get('{categoryId}/products', [CategoryController::class, 'getProductByCategory'])->withoutMiddleware('auth:sanctum');
        Route::get('/', [CategoryController::class, 'index'])->withoutMiddleware('auth:sanctum');
    });

    //DELIVERY DETAILS
    Route::group(['prefix' => 'delivery-details', 'middleware' => ['auth:sanctum']], function () {
        Route::get('/', [DeliveryDetailController::class, 'index']);
        Route::post('create ', [DeliveryDetailController::class, 'store']);
        Route::put('update/{id}', [DeliveryDetailController::class, 'update']);
        Route::delete('delete/{id}', [DeliveryDetailController::class, 'delete']);
    });

    //WISHLIST
    Route::group(['prefix' => 'wishlist', 'middleware' => ['auth:sanctum']], function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('create', [WishlistController::class, 'store']);
        Route::delete('delete/{id}', [WishlistController::class, 'delete']);
    });

    //CART
    Route::group(['prefix' => 'cart', 'middleware' => ['auth:sanctum']], function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('create', [CartController::class, 'store']);
        Route::put('update', [CartController::class, 'update']);
        Route::delete('delete/{id}', [CartController::class, 'delete']);
    });

    //ORDER
    Route::group(['prefix' => 'order', 'middleware' => ['auth:sanctum']], function () {
        Route::get('to-receive', [OrderController::class, 'getToReceiveOrders']);
        Route::get('cancelled', [OrderController::class, 'getCancelledOrders']);
        Route::post('create', [OrderController::class, 'store']);
        Route::put('cancel/{id}', [OrderController::class, 'cancelOrder']);
        Route::get('seller', [OrderController::class, 'getSellerOrders'])->Middleware('role:seller|agent');
        Route::put('seller/accept/{id}', [OrderController::class, 'acceptOrder'])->Middleware('role:seller|agent');
        Route::put('seller/decline/{id}', [OrderController::class, 'declineOrder'])->Middleware('role:seller|agent');
        Route::get('{id}', [OrderController::class, 'show']);
    });

    //SPECIFICATION
    Route::group(['prefix' => 'specification', 'middleware' => ['auth:sanctum']], function () {
        Route::get('{categoryId}', [SpecificationController::class, 'index']);
        Route::post('create', [SpecificationController::class, 'store']);
    });

    //CHAT
    Route::group(['prefix' => 'chat', 'middleware' => ['auth:sanctum']], function () {
        Route::get('conversation', [ChatController::class, 'getOrCreateConversation']);
        Route::get('messages/{conversationId}', [ChatController::class, 'getMessages']);
        Route::post('send', [ChatController::class, 'sendMessage']);
        Route::patch('conversation/{conversationId}/join', [ChatController::class, 'joinConversation']);
    });

});

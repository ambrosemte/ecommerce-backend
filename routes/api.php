<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CscController;
use App\Http\Controllers\DeliveryDetailController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RecentlyViewedController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\SpecificationController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WishlistController;
use App\Http\Middleware\AuthOrGuest;
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
    });

    //USER
    Route::group(['prefix' => 'user'], function () {
        // Routes accessible to only authenticated users
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('all', [UserController::class, 'getUsers'])
                ->middleware('role:admin');//admin

            Route::get('profile', [UserController::class, 'getProfile']);

            Route::post('profile/image/update', [UserController::class, 'updateProfileImage']);

            Route::get('check-authentication', [UserController::class, 'checkAuthentication']);

            Route::put('profile/update', [UserController::class, 'updateProfile']);

            Route::patch('preferred-currency', [UserController::class, 'setPreferredCurrency']);
        });

        // Routes accessible to both authenticated and guest users
        Route::middleware(AuthOrGuest::class)->group(function () {
            Route::patch('firebase-token', [UserController::class, 'updateFirebaseToken']);
        });
    });

    //STORE
    Route::group(['prefix' => 'store'], function () {

        Route::get('all', [StoreController::class, 'getStores'])
            ->middleware(['auth:sanctum', 'role:agent|admin']);

        Route::get('/', [StoreController::class, 'index'])
            ->middleware(['auth:sanctum', 'role:seller|agent']);

        Route::post('/', [StoreController::class, 'store'])
            ->middleware(['auth:sanctum', 'role:seller|agent']);

        Route::delete('{id}', [StoreController::class, 'delete'])
            ->middleware(['auth:sanctum', 'role:seller|agent']);

        Route::get('{id}', [StoreController::class, 'show'])
            ->middleware([AuthOrGuest::class]);

        Route::put('follow/{storeId}', [StoreController::class, 'follow'])
            ->middleware('auth:sanctum');

        Route::put('unfollow/{storeId}', [StoreController::class, 'unfollow'])
            ->middleware('auth:sanctum');
    });


    //PRODUCT
    Route::group(['prefix' => 'product'], function () {

        Route::get('/', [ProductController::class, 'sellerProducts'])
            ->middleware(['auth:sanctum', 'role:seller']);

        Route::get('featured-products', [ProductController::class, 'featuredProducts'])
            ->middleware([AuthOrGuest::class]);

        Route::get('search', [ProductController::class, 'searchProducts'])
            ->middleware([AuthOrGuest::class]);

        Route::get('search/seller', [ProductController::class, 'searchSellerProducts'])
            ->middleware(['auth:sanctum', 'role:seller']);

        Route::get('all', [ProductController::class, 'adminProducts'])
            ->middleware(['auth:sanctum', 'role:agent|admin']);

        Route::post('/', [ProductController::class, 'store'])
            ->middleware(['auth:sanctum', 'role:seller']);

        Route::get('{id}', [ProductController::class, 'show'])
            ->middleware([AuthOrGuest::class]);

        Route::post('{id}/update', [ProductController::class, 'update'])
            ->middleware(['auth:sanctum', 'role:seller|agent|admin']);

        Route::delete('{id}', [ProductController::class, 'delete'])
            ->middleware(['auth:sanctum', 'role:seller|agent|admin']);

        Route::delete('{productId}/variation/{variationId}', [ProductController::class, 'deleteVariation'])
            ->middleware(['auth:sanctum', 'role:seller|agent|admin']);

    });

    //CATEGORY
    Route::group(['prefix' => 'category'], function () {

        Route::get('{id}', [CategoryController::class, 'show'])
            ->middleware(['auth:sanctum', 'role:agent|admin']);//admin

        Route::get('{id}/products', [CategoryController::class, 'getProductByCategory'])
            ->middleware([AuthOrGuest::class]);

        Route::get('/', [CategoryController::class, 'index'])
            ->middleware([AuthOrGuest::class]);

        Route::post('/', [CategoryController::class, 'store'])
            ->middleware(['auth:sanctum', 'role:admin']);//admin

        Route::post('{id}/update', [CategoryController::class, 'update'])
            ->middleware(['auth:sanctum', 'role:admin']);

        Route::delete('{id}', [CategoryController::class, 'delete'])
            ->middleware(['auth:sanctum', 'role:admin']);
    });

    //DELIVERY DETAILS
    Route::group(['prefix' => 'delivery-details', 'middleware' => ['auth:sanctum']], function () {
        Route::get('/', [DeliveryDetailController::class, 'index']);
        Route::post('/', [DeliveryDetailController::class, 'store']);
        Route::put('{id}', [DeliveryDetailController::class, 'update']);
        Route::delete('{id}', [DeliveryDetailController::class, 'delete']);
    });

    //WISHLIST
    Route::group(['prefix' => 'wishlist', 'middleware' => [AuthOrGuest::class]], function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('/', [WishlistController::class, 'store']);
        Route::delete('/', [WishlistController::class, 'delete']);
    });

    //CART
    Route::group(['prefix' => 'cart'], function () {
        // Routes accessible to both authenticated and guest users
        Route::middleware([AuthOrGuest::class])->group(function () {
            Route::get('/', [CartController::class, 'index']);
            Route::post('/', [CartController::class, 'store']);
            Route::delete('{id}', [CartController::class, 'delete']);
        });

        // Routes only for authenticated users
        Route::middleware('auth:sanctum')->group(function () {
            Route::put('/', [CartController::class, 'update']);
        });
    });

    //ORDER
    Route::group(['prefix' => 'order', 'middleware' => ['auth:sanctum']], function () {
        // User routes
        Route::get('to-receive', [OrderController::class, 'getToReceiveOrders']);

        Route::get('cancelled', [OrderController::class, 'getCancelledOrders']);

        Route::get('without-review', [OrderController::class, 'getOrdersWithoutReview']);

        Route::get('activity', [OrderController::class, 'getOrdersActivity']);

        Route::post('/', [OrderController::class, 'store']);

        Route::put('cancel/{id}', [OrderController::class, 'cancelOrder']); // User cancel

        Route::put('request-refund/{id}', [OrderController::class, 'requestRefund']); // User Request Refund

        Route::get('/', [OrderController::class, 'getOrders']);

        // Seller/Admin routes
        Route::put('accept/{id}', [OrderController::class, 'acceptOrder'])
            ->middleware('role:seller|agent');

        Route::put('decline/{id}', [OrderController::class, 'declineOrder'])
            ->middleware('role:seller|agent');

        Route::put('process/{id}', [OrderController::class, 'processOrder'])
            ->middleware('role:agent|admin');

        Route::put('ship/{id}', [OrderController::class, 'shipOrder'])
            ->middleware('role:agent|admin');

        Route::put('out-for-delivery/{id}', [OrderController::class, 'outForDelivery'])
            ->middleware('role:agent|admin');

        Route::put('delivered/{id}', [OrderController::class, 'markAsDelivered'])
            ->middleware('role:agent|admin');

        Route::put('approve-refund/{id}', [OrderController::class, 'approveRefund'])
            ->middleware('role:agent|admin');

        Route::put('decline-refund/{id}', [OrderController::class, 'declineRefund'])
            ->middleware('role:agent|admin');

        // Single order
        Route::get('{id}', [OrderController::class, 'show']);
    });


    //SPECIFICATION
    Route::group(['prefix' => 'specification', 'middleware' => ['auth:sanctum']], function () {

        Route::get('{categoryId}', [SpecificationController::class, 'index']);

        Route::post('/', [SpecificationController::class, 'store'])
            ->middleware('role:admin');

        Route::patch('{id}', [SpecificationController::class, 'update'])
            ->middleware('role:admin');

        Route::delete('{id}', [SpecificationController::class, 'delete'])
            ->middleware('role:admin');
    });

    //CHAT
    Route::group(['prefix' => 'chat', 'middleware' => ['auth:sanctum']], function () {
        Route::post('conversation', [ChatController::class, 'createConversation']);

        Route::get('conversation', [ChatController::class, 'getConversationCustomer']);

        Route::get('conversation/agent', [ChatController::class, 'getConversationAgent']);

        Route::get('messages/{conversationId}', [ChatController::class, 'getMessages']);

        Route::post('send', [ChatController::class, 'sendMessage']);

        Route::post('conversation/{conversationId}/join', [ChatController::class, 'joinConversation'])
            ->middleware('role:agent|admin');

        Route::post('conversation/{conversationId}/transfer', [ChatController::class, 'transferConversation'])
            ->middleware('role:agent|admin');

        Route::post('conversation/{conversationId}/close', [ChatController::class, 'closeConversation'])
            ->middleware('role:agent|admin');
    });

    //NOTIFICATION
    Route::group(['prefix' => 'notification', 'middleware' => ['auth:sanctum']], function () {

        Route::get('/', [NotificationController::class, 'getUserNotifications']);

        Route::post('send', [NotificationController::class, 'sendNotification'])
            ->middleware('role:agent|admin');

        Route::patch('mark-as-read', [NotificationController::class, 'markAsRead']);
    });

    //RECENTLY VIEWED
    Route::group(['prefix' => 'recently-viewed', 'middleware' => [AuthOrGuest::class]], function () {
        Route::get('/', [RecentlyViewedController::class, 'index']);
    });

    //SHIPPING
    Route::group(['prefix' => "shipping", 'middleware' => ['auth:sanctum']], function () {
        Route::middleware('role:agent|admin')->group(function () {
            Route::prefix('method')->group(function () {
                Route::get('/', [ShippingController::class, 'getShippingMethods']);
                Route::post('/', [ShippingController::class, 'createShippingMethod']);
                Route::patch('{id}', [ShippingController::class, 'updateShippingMethod']);
            });

            Route::prefix('zone')->group(function () {
                Route::get('/', [ShippingController::class, 'getShippingZones']);
                Route::post('/', [ShippingController::class, 'createShippingZone']);
                Route::patch('{id}', [ShippingController::class, 'updateShippingZone']);
            });

            Route::prefix('rate')->group(function () {
                Route::get('{shippingZoneId?}', [ShippingController::class, 'getShippingRate']);
                Route::post('/', [ShippingController::class, 'createShippingRate']);
            });
        });

        Route::get('options/{deliveryDetailsId}', [ShippingController::class, 'getShippingOptions']);

    });

    //COUNTRY
    Route::group(['prefix' => "csc", 'middleware' => ['auth:sanctum']], function () {
        Route::get('countries', [CscController::class, 'getCountries']);
        Route::get('{countryId}/states', [CscController::class, 'getStates']);
        Route::get('{stateId}/cities', [CscController::class, 'getCities']);
    });

    //REVIEW
    Route::group(['prefix' => "review", 'middleware' => ['auth:sanctum']], function () {

        Route::get('all', [ReviewController::class, 'index'])
            ->middleware('role:agent|admin');

        Route::get('product', [ReviewController::class, 'getReviewsForProduct']);

        Route::post('/', [ReviewController::class, 'store']);

        Route::patch('approve/{id}', [ReviewController::class, 'approve'])
            ->middleware('role:agent|admin');

        Route::patch('decline/{id}', [ReviewController::class, 'decline'])
            ->middleware('role:agent|admin');
    });

    //STORIES
    Route::group(['prefix' => 'story'], function () {

        Route::get('feed', [StoryController::class, 'feed'])
            ->middleware([AuthOrGuest::class]);

        Route::get('all', [StoryController::class, 'adminStories'])
            ->middleware(['auth:sanctum', 'role:seller|agent|admin']);

        Route::get('store/{storeId}', [StoryController::class, 'showStoreStories'])
            ->middleware([AuthOrGuest::class]);

        Route::post('{storyId}/log-view', [StoryController::class, 'logView'])
            ->middleware([AuthOrGuest::class]);

        Route::post('/', [StoryController::class, 'store'])
            ->middleware(['auth:sanctum', 'role:seller|admin']);
    });

});

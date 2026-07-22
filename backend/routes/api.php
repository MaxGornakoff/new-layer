<?php

use App\Http\Controllers\Api\Admin\DeliverySettingController as AdminDeliverySettingController;
use App\Http\Controllers\Api\Admin\SiteSettingController as AdminSiteSettingController;
use App\Http\Controllers\Api\Admin\HeroSlideController as AdminHeroSlideController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\MenuItemController as AdminMenuItemController;
use App\Http\Controllers\Api\Admin\MenuSectionController as AdminMenuSectionController;
use App\Http\Controllers\Api\Admin\ClientController as AdminClientController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\StockController as AdminStockController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\HeroSlideController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\SiteSettingController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/site-settings', [SiteSettingController::class, 'show']);
Route::get('/hero-slides', [HeroSlideController::class, 'index']);
Route::get('/menu', [MenuController::class, 'index']);
Route::get('/menu-items', [MenuItemController::class, 'index']);
Route::get('/products/filters', [ProductController::class, 'filters']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product:slug}', [ProductController::class, 'show']);
Route::post('/delivery/calculate', [DeliveryController::class, 'calculate']);
Route::get('/delivery/providers', [DeliveryController::class, 'providers']);
Route::get('/delivery/cities', [DeliveryController::class, 'cities']);
Route::get('/delivery/pickup-points', [DeliveryController::class, 'pickupPoints']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [ProfileController::class, 'show']);
    Route::put('/user', [ProfileController::class, 'update']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('categories', [AdminCategoryController::class, 'index']);
        Route::post('categories', [AdminCategoryController::class, 'store']);
        Route::get('categories/{category}', [AdminCategoryController::class, 'show']);
        Route::match(['put', 'post'], 'categories/{category}', [AdminCategoryController::class, 'update']);
        Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy']);

        Route::apiResource('products', AdminProductController::class)->except(['update']);
        Route::match(['put', 'post'], 'products/{product}', [AdminProductController::class, 'update']);
        Route::post('products/{product}/stock', [AdminStockController::class, 'store']);

        Route::get('orders', [AdminOrderController::class, 'index']);
        Route::get('orders/new-count', [AdminOrderController::class, 'newCount']);
        Route::get('orders/{order}', [AdminOrderController::class, 'show']);
        Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
        Route::delete('orders/{order}', [AdminOrderController::class, 'destroy']);

        Route::apiResource('clients', AdminClientController::class)->except(['create', 'edit']);

        Route::get('menu-sections', [AdminMenuSectionController::class, 'index']);
        Route::post('menu-sections', [AdminMenuSectionController::class, 'store']);
        Route::put('menu-sections/{menuSection}', [AdminMenuSectionController::class, 'update']);
        Route::delete('menu-sections/{menuSection}', [AdminMenuSectionController::class, 'destroy']);

        Route::get('menu-items', [AdminMenuItemController::class, 'index']);
        Route::post('menu-items', [AdminMenuItemController::class, 'store']);
        Route::put('menu-items/{menuItem}', [AdminMenuItemController::class, 'update']);
        Route::delete('menu-items/{menuItem}', [AdminMenuItemController::class, 'destroy']);

        Route::get('hero-slides', [AdminHeroSlideController::class, 'index']);
        Route::post('hero-slides', [AdminHeroSlideController::class, 'store']);
        Route::match(['put', 'post'], 'hero-slides/{heroSlide}', [AdminHeroSlideController::class, 'update']);
        Route::delete('hero-slides/{heroSlide}', [AdminHeroSlideController::class, 'destroy']);

        Route::get('site-settings', [AdminSiteSettingController::class, 'show']);
        Route::post('site-settings', [AdminSiteSettingController::class, 'update']);

        Route::get('delivery-settings', [AdminDeliverySettingController::class, 'show']);
        Route::post('delivery-settings', [AdminDeliverySettingController::class, 'update']);
        Route::post('delivery-settings/test/baikal', [AdminDeliverySettingController::class, 'testBaikal']);
        Route::post('delivery-settings/test/dellin', [AdminDeliverySettingController::class, 'testDellin']);
        Route::post('delivery-settings/test/yandex', [AdminDeliverySettingController::class, 'testYandex']);
        Route::post('delivery-settings/test/zheldor', [AdminDeliverySettingController::class, 'testZheldor']);
        Route::post('delivery-settings/test/russian-post', [AdminDeliverySettingController::class, 'testRussianPost']);
    });
});

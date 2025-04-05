<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ZikorController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\StorefrontController;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use App\Http\Middleware\AdminAuthorization;


Route::group(['prefix' => '/oauth'], function () {
    Route::post('token', [AccessTokenController::class, 'issue']);
    Route::post('authorize', [AuthorizationController::class, 'authorize']);
    Route::post('refresh', [AccessTokenController::class, 'refresh']);
    Route::post('revoke', [AccessTokenController::class, 'revoke']);
});

// Public routes

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']); 

Route::get('/store/{slug}', [StorefrontController::class, 'getBySlug']);
Route::post('/business-info', [ProductController::class, 'getBusinessProducts']);

// Routes requiring API authentication
Route::middleware('auth:api')->group(function () {
    Route::get('/products', [ProductController::class, 'showProducts']);
    // Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::get('/categories', [ProductController::class, 'create']);
    Route::get('/business/name', [ProductController::class, 'getBusinessName']);


    //store front
    Route::get('/storefront', [StorefrontController::class, 'index']);
    Route::post('/storefront', [StorefrontController::class, 'store']);
    Route::put('/storefront', [StorefrontController::class, 'update']);
    Route::post('/storefront/check-slug', [StorefrontController::class, 'checkSlugAvailability']);
    
});



Route::middleware(['auth:api', \App\Http\Middleware\AdminAuthorization::class])->prefix('admin')->group(function () {
    // Admin controller routes (API)
    Route::get('dashboard', [AdminDashboardController::class, 'dashboard']);
     // User management
     Route::get('/users', [AdminDashboardController::class, 'showUsersWithProducts'])->name('admin.users.list');
     Route::delete('users/{userId}', [AdminDashboardController::class, 'deleteUser'])->name('admin.users.delete');
 
     // Product management
     Route::delete('users/{userId}/products/{productId}', [AdminDashboardController::class, 'deleteProduct'])->name('admin.products.delete');

     Route::post('categories', [AdminDashboardController::class, 'createCategory'])->name('admin.categories.create');
     Route::put('categories/{categoryId}', [AdminDashboardController::class, 'updateCategory'])->name('admin.categories.update');
     Route::delete('categories/{categoryId}', [AdminDashboardController::class, 'deleteCategory'])->name('admin.categories.delete');
     Route::get('categories', [AdminDashboardController::class, 'listCategories'])->name('admin.categories.list');
});


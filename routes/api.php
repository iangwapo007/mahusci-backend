<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;




/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public APIs
Route::post('/login', [AuthController::class, 'login'])->name('user.login');
Route::post('/register', [UserController::class,'store'])->name('user.store');

// Private APIs
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);

    Route::controller(UserController::class)->group(function () {
        Route::get('/user',                         'index');
        Route::get('/user/profile',                 'profile');
        Route::get('/user/{id}',                    'show');
        Route::put('/user/update/{id}',             'update')->name('user.update');
        Route::put('/user/update/{id}',             'update')->name('user.email');
        Route::delete('/user/{id}',                 'destroy');
    });
    
});

// Route::get('/test', [TestItemsController::class, 'index']);
// Route::get('/test/{id}', [TestItemsController::class, 'show']);
// Route::post('/test/add', [TestItemsController::class, 'store']);
// Route::put('/test/{id}', [TestItemsController::class, 'update']);
// Route::delete('/test/{id}', [TestItemsController::class, 'destroy']);





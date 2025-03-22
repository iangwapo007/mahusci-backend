<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TestItemsController;
use App\Http\Requests\TestItemsRequest;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/test', [TestItemsController::class, 'index']);
Route::get('/test/{id}', [TestItemsController::class, 'show']);
Route::post('/test/add', [TestItemsController::class, 'store']);
Route::put('/test/{id}', [TestItemsController::class, 'update']);
Route::delete('/test/{id}', [TestItemsController::class, 'destroy']);



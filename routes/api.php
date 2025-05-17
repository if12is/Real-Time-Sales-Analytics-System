<?php

use App\Http\Controllers\API\AnalyticsController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\RecommendationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Orders API
Route::post('/orders', [OrderController::class, 'store']);

// Analytics API
Route::get('/analytics', [AnalyticsController::class, 'getAnalytics']);

// Recommendations API
Route::get('/recommendations', [RecommendationController::class, 'getRecommendations']);

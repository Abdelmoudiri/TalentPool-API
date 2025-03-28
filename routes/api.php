<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JobOfferController;
use App\Http\Controllers\JobApplicationController;

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

// Authentication Routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Job Offers
    Route::prefix('job-offers')->group(function () {
        Route::get('/', [JobOfferController::class, 'index']);
        Route::post('/', [JobOfferController::class, 'store']);
        Route::get('/statistics', [JobOfferController::class, 'statistics']);
        Route::get('/{id}', [JobOfferController::class, 'show']);
        Route::put('/{id}', [JobOfferController::class, 'update']);
        Route::delete('/{id}', [JobOfferController::class, 'destroy']);
    });
    
    // Job Applications
    Route::prefix('applications')->group(function () {
        // Candidate routes
        Route::get('/my', [JobApplicationController::class, 'getMyApplications']);
        Route::post('/job/{jobOfferId}', [JobApplicationController::class, 'apply']);
        Route::delete('/{applicationId}', [JobApplicationController::class, 'withdraw']);
        
        // Recruiter routes
        Route::get('/job/{jobOfferId}', [JobApplicationController::class, 'getJobOfferApplications']);
        Route::put('/{applicationId}/status', [JobApplicationController::class, 'updateStatus']);
        
        // Common routes
        Route::get('/statistics', [JobApplicationController::class, 'statistics']);
        Route::get('/{applicationId}', [JobApplicationController::class, 'show']);
    });
});

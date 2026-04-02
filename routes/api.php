<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyProfileController;
use App\Http\Controllers\Api\StudentProfileController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\ApplicationController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/browse', [PositionController::class, 'browse']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Company routes
    Route::post('/company/profile', [CompanyProfileController::class, 'store']);
    Route::get('/company/profile', [CompanyProfileController::class, 'show']);
    Route::put('/company/profile', [CompanyProfileController::class, 'update']);

    // Student routes
    Route::post('/student/profile', [StudentProfileController::class, 'store']);
    Route::get('/student/profile', [StudentProfileController::class, 'show']);
    Route::put('/student/profile', [StudentProfileController::class, 'update']);

    //Position routes
    Route::post('/positions', [PositionController::class, 'store']);
    Route::get('/positions', [PositionController::class, 'index']);
    Route::get('/positions/{position}', [PositionController::class, 'show']);
    Route::put('/positions/{position}', [PositionController::class, 'update']);
    Route::patch('/positions/{position}/close', [PositionController::class, 'close']);

    // Student - apply and manage their applications
    Route::post('/positions/{position}/apply', [ApplicationController::class, 'store']);
    Route::get('/applications', [ApplicationController::class, 'myApplications']);
    Route::patch('/applications/{application}/withdraw', [ApplicationController::class, 'withdraw']);
    Route::patch('/applications/{application}/confirm', [ApplicationController::class, 'confirm']);

    // Company - manage applicants
    Route::get('/positions/{position}/applicants', [ApplicationController::class, 'applicants']);
    Route::patch('/applications/{application}/status', [ApplicationController::class, 'updateStatus']);
    Route::patch('/applications/{application}/accept', [ApplicationController::class, 'accept']);
});
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LearnerController;

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'Learner Enrollment API is working!',
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('jwt.auth')->group(function () {
   Route::get('/my-application', [LearnerController::class, 'myApplication'])
    ->middleware('role:learner');
    Route::post('/logout', [AuthController::class, 'logout']);

    // Authenticated learners can submit enrollment applications.
    Route::post('/learners', [LearnerController::class, 'store'])
    ->middleware('role:learner');

    // Only administrators can manage all learner records.
    Route::middleware('role:administrator')->group(function () {
        Route::get('/learners', [LearnerController::class, 'index']);
        Route::get('/learners/{learner}', [LearnerController::class, 'show']);
        Route::put('/learners/{learner}', [LearnerController::class, 'update']);
        Route::patch('/learners/{learner}', [LearnerController::class, 'update']);
        Route::delete('/learners/{learner}', [LearnerController::class, 'destroy']);
    });
});

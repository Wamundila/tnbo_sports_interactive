<?php

use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\TriviaQuizAdminController;
use App\Http\Controllers\Api\Admin\TriviaReportAdminController;
use App\Http\Controllers\Api\Predictor\PredictorCampaignController;
use App\Http\Controllers\Api\Predictor\PredictorEntryController;
use App\Http\Controllers\Api\Predictor\PredictorProfileController;
use App\Http\Controllers\Api\Trivia\TodayTriviaController;
use App\Http\Controllers\Api\Trivia\TriviaAttemptController;
use App\Http\Controllers\Api\Trivia\TriviaProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function (): void {
    Route::post('/auth/login', [AdminAuthController::class, 'login']);

    Route::middleware(['admin.auth', 'protected.api.logging'])->group(function (): void {
        Route::get('/auth/me', [AdminAuthController::class, 'me']);
        Route::post('/auth/logout', [AdminAuthController::class, 'logout']);

        Route::get('/overview', [TriviaReportAdminController::class, 'overview']);
        Route::get('/trivia/attempts', [TriviaReportAdminController::class, 'attempts']);
        Route::get('/trivia/leaderboards', [TriviaReportAdminController::class, 'leaderboards']);
        Route::get('/trivia/activity', [TriviaReportAdminController::class, 'activity']);

        Route::prefix('trivia/quizzes')->group(function (): void {
            Route::get('/', [TriviaQuizAdminController::class, 'index']);
            Route::post('/', [TriviaQuizAdminController::class, 'store']);
            Route::get('/{quiz}', [TriviaQuizAdminController::class, 'show']);
            Route::put('/{quiz}', [TriviaQuizAdminController::class, 'update']);
            Route::post('/{quiz}/publish', [TriviaQuizAdminController::class, 'publish']);
            Route::post('/{quiz}/close', [TriviaQuizAdminController::class, 'close']);
            Route::post('/{quiz}/duplicate', [TriviaQuizAdminController::class, 'duplicate']);
        });
    });
});

Route::prefix('v1')
    ->middleware(['service.auth', 'jwt.auth', 'protected.api.logging'])
    ->group(function (): void {
        Route::prefix('trivia')->group(function (): void {
            Route::get('/today', [TodayTriviaController::class, 'show']);
            Route::get('/summary', [TriviaProfileController::class, 'surfaceSummary']);
            Route::post('/today/start', [TriviaAttemptController::class, 'start'])
                ->middleware('verified.account');
            Route::post('/attempts/{attempt}/submit', [TriviaAttemptController::class, 'submit'])
                ->middleware('verified.account');
            Route::get('/me/summary', [TriviaProfileController::class, 'summary']);
            Route::get('/me/history', [TriviaProfileController::class, 'history']);
            Route::get('/leaderboards', [TriviaProfileController::class, 'leaderboard']);
        });

        Route::prefix('predictor')->group(function (): void {
            Route::get('/campaigns', [PredictorCampaignController::class, 'index']);
            Route::get('/summary', [PredictorCampaignController::class, 'summary']);
            Route::get('/campaigns/{campaign}', [PredictorCampaignController::class, 'show']);
            Route::get('/campaigns/{campaign}/current-round', [PredictorCampaignController::class, 'currentRound']);
            Route::get('/campaigns/{campaign}/leaderboards/{boardType}', [PredictorCampaignController::class, 'leaderboard']);
            Route::get('/rounds/{round}/my-entry', [PredictorEntryController::class, 'myEntry']);
            Route::post('/rounds/{round}/draft', [PredictorEntryController::class, 'draft'])
                ->middleware('verified.predictor');
            Route::post('/rounds/{round}/submit', [PredictorEntryController::class, 'submit'])
                ->middleware('verified.predictor');
            Route::get('/me/performance', [PredictorProfileController::class, 'performance']);
            Route::get('/me/history', [PredictorProfileController::class, 'history']);
        });
    });

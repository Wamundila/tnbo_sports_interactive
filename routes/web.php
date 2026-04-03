<?php

use App\Http\Controllers\Web\Admin\AuthController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\PollController;
use App\Http\Controllers\Web\Admin\PredictorCampaignController;
use App\Http\Controllers\Web\Admin\PredictorRoundController;
use App\Http\Controllers\Web\Admin\PredictorSeasonController;
use App\Http\Controllers\Web\Admin\QuizController;
use App\Http\Controllers\Web\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest:admin')->group(function (): void {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:admin')->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

        Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes.index');
        Route::get('/quizzes/create', [QuizController::class, 'create'])->name('quizzes.create');
        Route::post('/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
        Route::get('/quizzes/{quiz}/edit', [QuizController::class, 'edit'])->name('quizzes.edit');
        Route::put('/quizzes/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
        Route::post('/quizzes/{quiz}/publish', [QuizController::class, 'publish'])->name('quizzes.publish');
        Route::post('/quizzes/{quiz}/close', [QuizController::class, 'close'])->name('quizzes.close');
        Route::post('/quizzes/{quiz}/duplicate', [QuizController::class, 'duplicate'])->name('quizzes.duplicate');

        Route::get('/reports', ReportController::class)->name('reports.index');
        Route::view('/help/how-to', 'admin.help.how-to')->name('help.howto');

        Route::prefix('polls')->name('polls.')->group(function (): void {
            Route::get('/', [PollController::class, 'index'])->name('index');
            Route::get('/create', [PollController::class, 'create'])->name('create');
            Route::post('/', [PollController::class, 'store'])->name('store');
            Route::get('/{poll}/edit', [PollController::class, 'edit'])->name('edit');
            Route::put('/{poll}', [PollController::class, 'update'])->name('update');
            Route::post('/{poll}/publish', [PollController::class, 'publish'])->name('publish');
            Route::post('/{poll}/close', [PollController::class, 'close'])->name('close');
        });

        Route::prefix('predictor')->name('predictor.')->group(function (): void {
            Route::get('/', [PredictorCampaignController::class, 'index'])->name('index');
            Route::get('/campaigns/create', [PredictorCampaignController::class, 'create'])->name('campaigns.create');
            Route::post('/campaigns', [PredictorCampaignController::class, 'store'])->name('campaigns.store');
            Route::get('/campaigns/{campaign}/edit', [PredictorCampaignController::class, 'edit'])->name('campaigns.edit');
            Route::put('/campaigns/{campaign}', [PredictorCampaignController::class, 'update'])->name('campaigns.update');

            Route::get('/campaigns/{campaign}/seasons/create', [PredictorSeasonController::class, 'create'])->name('campaigns.seasons.create');
            Route::post('/campaigns/{campaign}/seasons', [PredictorSeasonController::class, 'store'])->name('campaigns.seasons.store');
            Route::get('/seasons/{season}/edit', [PredictorSeasonController::class, 'edit'])->name('seasons.edit');
            Route::put('/seasons/{season}', [PredictorSeasonController::class, 'update'])->name('seasons.update');

            Route::get('/seasons/{season}/rounds/create', [PredictorRoundController::class, 'create'])->name('seasons.rounds.create');
            Route::post('/seasons/{season}/rounds', [PredictorRoundController::class, 'store'])->name('seasons.rounds.store');
            Route::get('/rounds/{round}/edit', [PredictorRoundController::class, 'edit'])->name('rounds.edit');
            Route::put('/rounds/{round}', [PredictorRoundController::class, 'update'])->name('rounds.update');
            Route::post('/rounds/{round}/transition', [PredictorRoundController::class, 'transition'])->name('rounds.transition');
            Route::post('/rounds/{round}/score', [PredictorRoundController::class, 'score'])->name('rounds.score');
            Route::post('/rounds/{round}/recalculate', [PredictorRoundController::class, 'recalculate'])->name('rounds.recalculate');
        });
    });
});

<?php

use App\Http\Controllers\Web\Admin\AuthController;
use App\Http\Controllers\Web\Admin\DashboardController;
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
    });
});

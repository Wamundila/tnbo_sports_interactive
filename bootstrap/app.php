<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\EnsureAdminApiToken;
use App\Http\Middleware\EnsureInternalServiceRequest;
use App\Http\Middleware\EnsureVerifiedPredictorUser;
use App\Http\Middleware\EnsureVerifiedTriviaUser;
use App\Http\Middleware\LogProtectedApiRequest;
use App\Http\Middleware\VerifyJwtToken;
use App\Http\Middleware\VerifyOptionalJwtToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));

        $middleware->alias([
            'jwt.auth' => VerifyJwtToken::class,
            'jwt.optional' => VerifyOptionalJwtToken::class,
            'admin.auth' => EnsureAdminApiToken::class,
            'service.auth' => EnsureInternalServiceRequest::class,
            'verified.account' => EnsureVerifiedTriviaUser::class,
            'verified.predictor' => EnsureVerifiedPredictorUser::class,
            'protected.api.logging' => LogProtectedApiRequest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ApiException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json($exception->toArray(), $exception->status());
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'The request payload is invalid.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $exception->errors(),
            ], 422);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage() ?: 'HTTP request failed.',
                'code' => 'HTTP_ERROR',
            ], $exception->getStatusCode());
        });
    })->create();

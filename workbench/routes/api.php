<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tests\Fixtures\Support\Http\Api\Errors\ErrorsController;
use Tests\Fixtures\Support\Http\Api\Errors\LegacyErrorsController;

Route::middleware('api')->prefix('api/errors')->group(function (): void {
    Route::get('authenticated', [ErrorsController::class, 'authenticated'])->middleware('auth');
    Route::get('forbidden', [ErrorsController::class, 'forbidden']);
    Route::get('articles/{article}', [ErrorsController::class, 'show']);
    Route::get('method-not-allowed', [ErrorsController::class, 'methodNotAllowed']);
    Route::match(['GET', 'POST'], 'validate', [ErrorsController::class, 'validate']);
    Route::match(['GET', 'POST'], 'rules', [ErrorsController::class, 'rules']);
    Route::post('after', [ErrorsController::class, 'after']);
    Route::get('throttled', [ErrorsController::class, 'throttled'])->middleware('throttle:1,1');
    Route::post('precondition-failed', [ErrorsController::class, 'preconditionFailed']);
    Route::post('precondition-required', [ErrorsController::class, 'preconditionRequired']);
    Route::get('fail', [ErrorsController::class, 'fail']);
    Route::post('legacy/validate', [LegacyErrorsController::class, 'validate']);
    Route::post('legacy/precondition-failed', [LegacyErrorsController::class, 'preconditionFailed']);
});

Route::post('errors/outside/validate', [ErrorsController::class, 'validate']);

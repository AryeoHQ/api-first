<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tests\Fixtures\Support\Http\Api\Errors\ErrorsController;
use Tests\Fixtures\Support\Http\Api\Errors\LegacyErrorsController;

Route::middleware('api')->prefix('api/errors')->group(function (): void {
    Route::get('authenticated', [ErrorsController::class, 'authenticated'])->middleware('auth');
    Route::get('forbidden', [ErrorsController::class, 'forbidden']);
    Route::get('articles/{article}', [ErrorsController::class, 'show']);
    Route::get('articles/{article}/hidden', [ErrorsController::class, 'hidden']);
    Route::get('missing-scope', [ErrorsController::class, 'missingScope']);
    Route::get('missing-ability', [ErrorsController::class, 'missingAbility']);
    Route::get('method-not-allowed', [ErrorsController::class, 'methodNotAllowed']);
    Route::match(['GET', 'POST'], 'validate', [ErrorsController::class, 'validate']);
    Route::match(['GET', 'POST'], 'rules', [ErrorsController::class, 'rules']);
    Route::post('after', [ErrorsController::class, 'after']);
    Route::get('validation-status', [ErrorsController::class, 'validationStatus']);
    Route::get('bad-request', [ErrorsController::class, 'badRequest']);
    Route::get('unsupported-media-type', [ErrorsController::class, 'unsupportedMediaType']);
    Route::get('unavailable', [ErrorsController::class, 'unavailable']);
    Route::get('abort/{status}', [ErrorsController::class, 'abort']);
    Route::get('abort/{status}/headers', [ErrorsController::class, 'abortWithHeaders']);
    Route::post('custom-rules', [ErrorsController::class, 'customRules']);
    Route::post('with-messages', [ErrorsController::class, 'withMessages']);
    Route::get('throttled', [ErrorsController::class, 'throttled'])->middleware('throttle:1,1');
    Route::get('throttled-until', [ErrorsController::class, 'throttledUntil']);
    Route::post('precondition-failed', [ErrorsController::class, 'preconditionFailed']);
    Route::post('precondition-required', [ErrorsController::class, 'preconditionRequired']);
    Route::post('precondition-failed-with-headers', [ErrorsController::class, 'preconditionFailedWithHeaders']);
    Route::post('symfony/precondition-failed', [ErrorsController::class, 'symfonyPreconditionFailed']);
    Route::post('symfony/precondition-required', [ErrorsController::class, 'symfonyPreconditionRequired']);
    Route::get('fail', [ErrorsController::class, 'fail']);
    Route::post('legacy/validate', [LegacyErrorsController::class, 'validate']);
    Route::post('legacy/precondition-failed', [LegacyErrorsController::class, 'preconditionFailed']);
    Route::post('legacy/symfony/precondition-failed', [LegacyErrorsController::class, 'symfonyPreconditionFailed']);
    Route::get('legacy/abort/{status}', [LegacyErrorsController::class, 'abort']);
});

Route::post('errors/outside/validate', [ErrorsController::class, 'validate']);

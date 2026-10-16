<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tests\Fixtures\Support\Http\Api\Errors\ErrorsController;
use Tests\Fixtures\Support\Http\Api\Errors\LegacyErrorsController;
use Tests\Fixtures\Support\Http\Api\Preconditions\ForbiddenUpdateNoteController;
use Tests\Fixtures\Support\Http\Api\Preconditions\RequiredUpdateNoteController;
use Tests\Fixtures\Support\Http\Api\Preconditions\ShowNoteController;
use Tests\Fixtures\Support\Http\Api\Preconditions\UpdateNoteController;
use Tests\Fixtures\Support\Http\Api\Preconditions\UpdateSecondaryNoteController;

Route::middleware('api')->prefix('api/errors')->group(function (): void {
    Route::get('authenticated', [ErrorsController::class, 'authenticated'])->middleware('auth');
    Route::get('forbidden', [ErrorsController::class, 'forbidden']);
    Route::get('articles/{article}', [ErrorsController::class, 'show']);
    Route::get('articles/{article}/hidden', [ErrorsController::class, 'hidden']);
    Route::get('missing-scope', [ErrorsController::class, 'missingScope']);
    Route::get('method-not-allowed', [ErrorsController::class, 'methodNotAllowed']);
    Route::match(['GET', 'POST'], 'validate', [ErrorsController::class, 'validate']);
    Route::match(['GET', 'POST'], 'rules', [ErrorsController::class, 'rules']);
    Route::post('after', [ErrorsController::class, 'after']);
    Route::get('validation-status', [ErrorsController::class, 'validationStatus']);
    Route::get('bad-request', [ErrorsController::class, 'badRequest']);
    Route::get('unsupported-media-type', [ErrorsController::class, 'unsupportedMediaType']);
    Route::get('unavailable', [ErrorsController::class, 'unavailable']);
    Route::get('abort/{status}', [ErrorsController::class, 'abort']);
    Route::post('uncatalogued', [ErrorsController::class, 'uncatalogued']);
    Route::post('with-messages', [ErrorsController::class, 'withMessages']);
    Route::get('throttled', [ErrorsController::class, 'throttled'])->middleware('throttle:1,1');
    Route::post('precondition-failed', [ErrorsController::class, 'preconditionFailed']);
    Route::post('precondition-required', [ErrorsController::class, 'preconditionRequired']);
    Route::get('fail', [ErrorsController::class, 'fail']);
    Route::post('legacy/validate', [LegacyErrorsController::class, 'validate']);
    Route::post('legacy/precondition-failed', [LegacyErrorsController::class, 'preconditionFailed']);
});

Route::post('errors/outside/validate', [ErrorsController::class, 'validate']);

Route::middleware('api')->prefix('api/preconditions')->group(function (): void {
    Route::get('notes/{note}', ShowNoteController::class);
    Route::patch('notes/{note}', UpdateNoteController::class);
    Route::patch('notes/{note}/required', RequiredUpdateNoteController::class);
    Route::patch('notes/{note}/forbidden', ForbiddenUpdateNoteController::class);
    Route::patch('notes/{note}/trashed', UpdateNoteController::class)->withTrashed();
    Route::patch('secondary-notes/{note}', UpdateSecondaryNoteController::class);
});

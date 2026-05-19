<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Models\Concerns;

/**
 * @template TModel of LogsSchemasModel
 *
 * @extends \Illuminate\Database\Eloquent\Builder<TModel>
 */
final class LogsSchemasBuilder extends \Illuminate\Database\Eloquent\Builder {}

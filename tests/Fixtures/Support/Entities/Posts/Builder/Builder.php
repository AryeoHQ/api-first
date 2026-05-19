<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Posts\Builder;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * @extends EloquentBuilder<\Tests\Fixtures\Support\Entities\Posts\Post>
 */
final class Builder extends EloquentBuilder {}

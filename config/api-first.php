<?php

declare(strict_types=1);

return [
    'errors' => [
        'paths' => ['api', 'api/*'],
    ],
    'conditional_get' => [
        'middleware_groups' => ['api'],
    ],
];

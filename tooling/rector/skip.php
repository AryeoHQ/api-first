<?php

use Tooling\Rector\Rules\AddInterfaceByClass;

return [
    AddInterfaceByClass::class => [
        __DIR__.'/../../tests/Fixtures/Support/Http/Api/Resources/Json/PaginatedResourceResponse/PagedPost.php',
    ],
];

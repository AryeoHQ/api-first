<?php

declare(strict_types=1);

namespace Support\Entities\Database\Query\Grammars\Concerns;

trait ReadsDateFormatFromConfig
{
    public function getDateFormat(): string
    {
        return $this->connection->getConfig('date_format') ?? parent::getDateFormat();
    }
}

<?php

declare(strict_types=1);

namespace Support\Entities\Database\Query\Grammars\Concerns;

trait WithConfigurableDateFormat
{
    public function getDateFormat(): string
    {
        return $this->connection->getConfig('date_format') ?? parent::getDateFormat();
    }
}

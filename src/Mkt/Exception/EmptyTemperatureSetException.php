<?php

declare(strict_types=1);

namespace App\Mkt\Exception;

final class EmptyTemperatureSetException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Cannot calculate MKT for an empty set of temperatures.');
    }
}
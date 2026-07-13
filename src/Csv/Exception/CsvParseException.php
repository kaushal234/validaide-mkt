<?php

declare(strict_types=1);

namespace App\Csv\Exception;

final class CsvParseException extends \RuntimeException
{
    public function __construct(private readonly array $errors)
    {
        parent::__construct(implode(' ', $errors));
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
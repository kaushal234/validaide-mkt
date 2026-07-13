<?php

declare(strict_types=1);

namespace App\Csv;

use App\Csv\Exception\CsvParseException;

interface TemperatureCsvParserInterface
{
    /**
     * @return list<ParsedReading>
     *
     * @throws CsvParseException
     */
    public function parse(\SplFileObject $file): array;
}
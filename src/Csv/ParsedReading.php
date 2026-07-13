<?php

declare(strict_types=1);

namespace App\Csv;

final class ParsedReading
{
    public function __construct(
        public readonly \DateTimeImmutable $recordedAt,
        public readonly float $temperature,
    ) {
    }
}
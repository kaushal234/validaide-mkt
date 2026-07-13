<?php

declare(strict_types=1);

namespace App\Csv;

use App\Csv\Exception\CsvParseException;

final class TemperatureCsvParser implements TemperatureCsvParserInterface
{
    public function parse(\SplFileObject $file): array
    {
        $rows = $this->readRows($file);

        if ([] === $rows) {
            throw new CsvParseException(['The file is empty.']);
        }

        $columns = $this->resolveColumns($rows[0]);
        $startIndex = $columns['hasHeader'] ? 1 : 0;

        $errors = [];
        $readings = [];

        for ($i = $startIndex, $count = \count($rows); $i < $count; ++$i) {
            $rowNumber = $i + 1;
            $cells = $rows[$i];

            if ($this->isBlank($cells)) {
                continue;
            }

            if (\count($cells) <= max($columns['time'], $columns['temperature'])) {
                $errors[] = \sprintf('Row %d: expected at least two columns (time and temperature).', $rowNumber);
                continue;
            }

            $rawTime = trim($cells[$columns['time']]);
            $rawTemperature = trim($cells[$columns['temperature']]);

            $recordedAt = $this->parseTime($rawTime);
            $temperature = $this->parseTemperature($rawTemperature);

            if (null === $recordedAt) {
                $errors[] = \sprintf('Row %d: could not read the time "%s".', $rowNumber, $rawTime);
            }

            if (null === $temperature) {
                $errors[] = \sprintf('Row %d: the temperature "%s" is not a number.', $rowNumber, $rawTemperature);
            }

            if (null !== $recordedAt && null !== $temperature) {
                $readings[] = new ParsedReading($recordedAt, $temperature);
            }
        }

        if ([] === $readings && [] === $errors) {
            throw new CsvParseException(['The file does not contain any data rows.']);
        }

        if ([] !== $errors) {
            throw new CsvParseException($errors);
        }

        return $readings;
    }

    private function readRows(\SplFileObject $file): array
    {
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD);

        $rows = [];
        foreach ($file as $row) {
            if (false === $row || [null] === $row || null === $row) {
                continue;
            }
            $rows[] = array_map(static fn ($value): string => (string) $value, $row);
        }

        return $rows;
    }

    private function resolveColumns(array $firstRow): array
    {
        $isData = null !== $this->parseTime(trim($firstRow[0] ?? ''))
            && null !== $this->parseTemperature(trim($firstRow[1] ?? ''));

        if ($isData) {
            return ['hasHeader' => false, 'time' => 0, 'temperature' => 1];
        }

        $lowered = array_map(static fn (string $value): string => strtolower(trim($value)), $firstRow);
        $timeIndex = array_search('time', $lowered, true);
        $temperatureIndex = array_search('temperature', $lowered, true);

        if (false !== $timeIndex && false !== $temperatureIndex) {
            return ['hasHeader' => true, 'time' => $timeIndex, 'temperature' => $temperatureIndex];
        }

        return ['hasHeader' => true, 'time' => 0, 'temperature' => 1];
    }

    private function parseTime(string $value): ?\DateTimeImmutable
    {
        if ('' === $value) {
            return null;
        }

        if (1 === preg_match('/^\d+$/', $value)) {
            return (new \DateTimeImmutable())->setTimestamp((int) $value);
        }

        foreach (['Y-m-d H:i:s', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!'.$format, $value);
            if (false !== $date) {
                return $date;
            }
        }

        return null;
    }

    private function parseTemperature(string $value): ?float
    {
        if (1 !== preg_match('/^[+-]?\d+(\.\d+)?$/', $value)) {
            return null;
        }

        return (float) $value;
    }

    private function isBlank(array $cells): bool
    {
        foreach ($cells as $cell) {
            if ('' !== trim((string) $cell)) {
                return false;
            }
        }

        return true;
    }
}
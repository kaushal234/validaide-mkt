<?php

declare(strict_types=1);

namespace App\Csv;

use App\Csv\Exception\CsvParseException;

final class TemperatureCsvParser implements TemperatureCsvParserInterface
{
    private const float MIN_TEMPERATURE = -100.0;
    private const float MAX_TEMPERATURE = 100.0;
    private const int MAX_ROWS = 100000;

    public function parse(\SplFileObject $file): array
    {
        $rows = $this->readRows($file);

        if ([] === $rows) {
            throw new CsvParseException(['The file is empty.']);
        }

        $columns = $this->resolveColumns($rows[0]);

        if (null === $columns) {
            throw new CsvParseException(['Could not find the "time" and "temperature" columns. The file does not look like temperature data.']);
        }

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
                $errors[] = \sprintf('Row %d: the temperature "%s" is not a valid number between %g and %g °C.', $rowNumber, $rawTemperature, self::MIN_TEMPERATURE, self::MAX_TEMPERATURE);
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

            if (\count($rows) >= self::MAX_ROWS) {
                throw new CsvParseException([\sprintf('The file has too many rows (more than %s). Please upload a smaller dataset.', number_format(self::MAX_ROWS))]);
            }

            $rows[] = array_map(static fn ($value): string => (string) $value, $row);
        }

        return $rows;
    }

    private function resolveColumns(array $firstRow): ?array
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

        return null;
    }

    private function parseTime(string $value): ?\DateTimeImmutable
    {
        if ('' === $value || !$this->isPrintable($value)) {
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
        if (!$this->isPrintable($value) || 1 !== preg_match('/^[+-]?\d+(\.\d+)?$/', $value)) {
            return null;
        }

        $temperature = (float) $value;

        if ($temperature < self::MIN_TEMPERATURE || $temperature > self::MAX_TEMPERATURE) {
            return null;
        }

        return $temperature;
    }

    private function isPrintable(string $value): bool
    {
        return 0 === preg_match('/[\x00-\x08\x0E-\x1F]/', $value);
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
<?php

declare(strict_types=1);

namespace App\Tests\Csv;

use App\Csv\Exception\CsvParseException;
use App\Csv\TemperatureCsvParser;
use PHPUnit\Framework\TestCase;

final class TemperatureCsvParserTest extends TestCase
{
    private TemperatureCsvParser $parser;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        $this->parser = new TemperatureCsvParser();
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testParsesHeaderedIsoFile(): void
    {
        $readings = $this->parse(<<<CSV
            time,temperature
            2026-07-01 08:00:00,22.5
            2026-07-01 09:00:00,24.1
            CSV);

        self::assertCount(2, $readings);
        self::assertSame('2026-07-01 08:00:00', $readings[0]->recordedAt->format('Y-m-d H:i:s'));
        self::assertSame(22.5, $readings[0]->temperature);
    }

    public function testParsesFileWithoutHeaderPositionally(): void
    {
        $readings = $this->parse(<<<CSV
            2026-07-01 08:00:00,22.5
            2026-07-01 09:00:00,24.1
            CSV);

        self::assertCount(2, $readings);
    }

    public function testParsesUnixTimestamps(): void
    {
        $readings = $this->parse(<<<CSV
            time,temperature
            1782979200,20
            1782982800,25
            CSV);

        self::assertCount(2, $readings);
        self::assertSame(20.0, $readings[0]->temperature);
    }

    public function testReadsColumnsByHeaderNameRegardlessOfOrder(): void
    {
        $readings = $this->parse(<<<CSV
            temperature,time
            22.5,2026-07-01 08:00:00
            CSV);

        self::assertCount(1, $readings);
        self::assertSame(22.5, $readings[0]->temperature);
        self::assertSame('2026-07-01 08:00:00', $readings[0]->recordedAt->format('Y-m-d H:i:s'));
    }

    public function testSkipsBlankLines(): void
    {
        $readings = $this->parse(<<<CSV
            time,temperature
            2026-07-01 08:00:00,22.5

            2026-07-01 10:00:00,25.0
            CSV);

        self::assertCount(2, $readings);
    }

    public function testRejectsNonNumericTemperature(): void
    {
        $errors = $this->parseExpectingErrors(<<<CSV
            time,temperature
            2026-07-01 08:00:00,abc
            CSV);

        self::assertStringContainsString('not a valid number', $errors[0]);
    }

    public function testRejectsUnparseableTime(): void
    {
        $errors = $this->parseExpectingErrors(<<<CSV
            time,temperature
            tomorrow,22.5
            CSV);

        self::assertStringContainsString('could not read the time', $errors[0]);
    }

    public function testRejectsCommaDecimalTemperature(): void
    {
        $errors = $this->parseExpectingErrors(<<<CSV
            time,temperature
            2026-07-01 08:00:00,"22,5"
            CSV);

        self::assertStringContainsString('not a valid number', $errors[0]);
    }

    public function testRejectsRowWithMissingColumn(): void
    {
        $errors = $this->parseExpectingErrors(<<<CSV
            time,temperature
            2026-07-01 08:00:00
            CSV);

        self::assertStringContainsString('at least two columns', $errors[0]);
    }

    public function testCollectsMultipleErrors(): void
    {
        $errors = $this->parseExpectingErrors(<<<CSV
            time,temperature
            2026-07-01 08:00:00,abc
            tomorrow,24.1
            CSV);

        self::assertCount(2, $errors);
    }

    public function testRejectsFileWhoseHeaderLacksExpectedColumns(): void
    {
        $errors = $this->parseExpectingErrors(<<<CSV
            id,name,owner_id
            1,SOL,12171
            2,CRT,108
            CSV);

        self::assertStringContainsString('time', strtolower($errors[0]));
        self::assertStringContainsString('temperature', strtolower($errors[0]));
    }

    public function testRejectsImplausiblyHighTemperature(): void
    {
        $errors = $this->parseExpectingErrors(<<<CSV
            time,temperature
            2026-07-01 08:00:00,12171
            CSV);

        self::assertStringContainsString('12171', $errors[0]);
    }

    public function testRejectsImplausiblyLowTemperature(): void
    {
        $errors = $this->parseExpectingErrors(<<<CSV
            time,temperature
            2026-07-01 08:00:00,-500
            CSV);

        self::assertCount(1, $errors);
    }

    public function testAcceptsTemperatureAtRangeBoundaries(): void
    {
        $readings = $this->parse(<<<CSV
            time,temperature
            2026-07-01 08:00:00,-100
            2026-07-01 09:00:00,100
            CSV);

        self::assertCount(2, $readings);
    }

    public function testRejectsFileExceedingRowLimit(): void
    {
        $lines = ['time,temperature'];
        for ($i = 0; $i < 100001; ++$i) {
            $lines[] = '2026-07-01 08:00:00,20';
        }

        $errors = $this->parseExpectingErrors(implode("\n", $lines));

        self::assertStringContainsString('too many rows', $errors[0]);
    }

    public function testRejectsEmptyFile(): void
    {
        $errors = $this->parseExpectingErrors('');

        self::assertStringContainsString('empty', strtolower($errors[0]));
    }

    public function testRejectsHeaderOnlyFile(): void
    {
        $errors = $this->parseExpectingErrors('time,temperature');

        self::assertStringContainsString('does not contain any data', strtolower($errors[0]));
    }

    /**
     * @return list<\App\Csv\ParsedReading>
     */
    private function parse(string $contents): array
    {
        return $this->parser->parse($this->fileFrom($contents));
    }

    /**
     * @return list<string>
     */
    private function parseExpectingErrors(string $contents): array
    {
        try {
            $this->parser->parse($this->fileFrom($contents));
            self::fail('Expected CsvParseException was not thrown.');
        } catch (CsvParseException $exception) {
            return $exception->getErrors();
        }
    }

    private function fileFrom(string $contents): \SplFileObject
    {
        $path = tempnam(sys_get_temp_dir(), 'mkt_csv_');
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return new \SplFileObject($path);
    }
}
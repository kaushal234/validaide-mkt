<?php

declare(strict_types=1);

namespace App\Tests\Mkt;

use App\Mkt\Exception\EmptyTemperatureSetException;
use App\Mkt\MeanKineticTemperatureCalculator;
use PHPUnit\Framework\TestCase;

final class MeanKineticTemperatureCalculatorTest extends TestCase
{
    private MeanKineticTemperatureCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new MeanKineticTemperatureCalculator();
    }

    public function testConstantTemperatureReturnsThatTemperature(): void
    {
        $result = $this->calculator->calculate([25.0, 25.0, 25.0, 25.0]);

        self::assertEqualsWithDelta(25.0, $result, 0.0001);
    }

    public function testSingleReading(): void
    {
        $result = $this->calculator->calculate([30.0]);

        self::assertEqualsWithDelta(30.0, $result, 0.0001);
    }

    public function testMktIsAboveArithmeticMean(): void
    {
        $temperatures = [20.0, 20.0, 40.0, 25.0];
        $mean = array_sum($temperatures) / count($temperatures);

        $result = $this->calculator->calculate($temperatures);

        self::assertGreaterThan($mean, $result);
        self::assertEqualsWithDelta(30.208237, $result, 0.0001);
    }

    public function testKnownValueForTwoTemperatures(): void
    {
        $result = $this->calculator->calculate([20.0, 40.0]);

        self::assertEqualsWithDelta(34.3579, $result, 0.0001);
    }

    public function testLargerSpreadPullsMktFurtherFromMean(): void
    {
        $result = $this->calculator->calculate([10.0, 10.0, 10.0, 50.0]);

        self::assertEqualsWithDelta(36.5004, $result, 0.0001);
    }

    public function testNegativeTemperatures(): void
    {
        $result = $this->calculator->calculate([-10.0, -5.0, 0.0]);

        self::assertGreaterThan(-5.0, $result);
    }

    public function testEmptySetThrows(): void
    {
        $this->expectException(EmptyTemperatureSetException::class);

        $this->calculator->calculate([]);
    }
}
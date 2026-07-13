<?php

declare(strict_types=1);

namespace App\Mkt;

use App\Mkt\Exception\EmptyTemperatureSetException;

final class MeanKineticTemperatureCalculator
{
    private const float ACTIVATION_ENERGY = 83.144;
    private const float GAS_CONSTANT = 0.0083144;
    private const float CELSIUS_TO_KELVIN = 273.15;

    public function calculate(array $celsiusTemperatures): float
    {
        if ([] === $celsiusTemperatures) {
            throw new EmptyTemperatureSetException();
        }

        $factor = self::ACTIVATION_ENERGY / self::GAS_CONSTANT;

        $sum = 0.0;
        foreach ($celsiusTemperatures as $celsius) {
            $kelvin = $celsius + self::CELSIUS_TO_KELVIN;
            $sum += exp(-$factor / $kelvin);
        }

        $average = $sum / count($celsiusTemperatures);
        $kelvin = $factor / (-log($average));

        return $kelvin - self::CELSIUS_TO_KELVIN;
    }
}
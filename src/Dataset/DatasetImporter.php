<?php

declare(strict_types=1);

namespace App\Dataset;

use App\Csv\TemperatureCsvParserInterface;
use App\Entity\Dataset;
use App\Entity\Reading;
use App\Mkt\MeanKineticTemperatureCalculator;
use Doctrine\ORM\EntityManagerInterface;

final class DatasetImporter
{
    public function __construct(
        private readonly TemperatureCsvParserInterface $parser,
        private readonly MeanKineticTemperatureCalculator $calculator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function import(string $name, \SplFileObject $file): Dataset
    {
        $parsedReadings = $this->parser->parse($file);

        $dataset = new Dataset();
        $dataset->setName($name);

        $temperatures = [];
        foreach ($parsedReadings as $parsedReading) {
            $dataset->addReading(new Reading($parsedReading->recordedAt, $parsedReading->temperature));
            $temperatures[] = $parsedReading->temperature;
        }

        $dataset->setMkt($this->calculator->calculate($temperatures));

        $this->entityManager->persist($dataset);
        $this->entityManager->flush();

        return $dataset;
    }
}
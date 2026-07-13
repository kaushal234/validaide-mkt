<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReadingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReadingRepository::class)]
class Reading
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $recordedAt;

    #[ORM\Column]
    private float $temperature;

    #[ORM\ManyToOne(targetEntity: Dataset::class, inversedBy: 'readings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dataset $dataset = null;

    public function __construct(\DateTimeImmutable $recordedAt, float $temperature)
    {
        $this->recordedAt = $recordedAt;
        $this->temperature = $temperature;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecordedAt(): \DateTimeImmutable
    {
        return $this->recordedAt;
    }

    public function getTemperature(): float
    {
        return $this->temperature;
    }

    public function getDataset(): ?Dataset
    {
        return $this->dataset;
    }

    public function setDataset(?Dataset $dataset): static
    {
        $this->dataset = $dataset;

        return $this;
    }
}
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DatasetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DatasetRepository::class)]
class Dataset
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $submittedAt;

    #[ORM\Column(nullable: true)]
    private ?float $mkt = null;

    #[ORM\OneToMany(targetEntity: Reading::class, mappedBy: 'dataset', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $readings;

    public function __construct()
    {
        $this->readings = new ArrayCollection();
        $this->submittedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSubmittedAt(): \DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function getMkt(): ?float
    {
        return $this->mkt;
    }

    public function setMkt(?float $mkt): static
    {
        $this->mkt = $mkt;

        return $this;
    }

    public function getReadings(): Collection
    {
        return $this->readings;
    }

    public function addReading(Reading $reading): static
    {
        if (!$this->readings->contains($reading)) {
            $this->readings->add($reading);
            $reading->setDataset($this);
        }

        return $this;
    }

    public function removeReading(Reading $reading): static
    {
        if ($this->readings->removeElement($reading)) {
            if ($reading->getDataset() === $this) {
                $reading->setDataset(null);
            }
        }

        return $this;
    }
}
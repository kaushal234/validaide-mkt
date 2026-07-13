<?php

declare(strict_types=1);

namespace App\Dataset\Dto;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

final class DatasetUploadDto
{
    #[Assert\NotBlank(message: 'Please give the dataset a name.')]
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\NotNull(message: 'Please choose a CSV file to upload.')]
    #[Assert\File(maxSize: '5M')]
    public ?File $file = null;
}
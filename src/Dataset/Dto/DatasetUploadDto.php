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
    #[Assert\File(
        maxSize: '2M',
        extensions: ['csv'],
        extensionsMessage: 'Please upload a CSV file (.csv).',
        maxSizeMessage: 'The file is too large ({{ size }} {{ suffix }}). The maximum allowed size is {{ limit }} {{ suffix }}.',
    )]
    public ?File $file = null;
}
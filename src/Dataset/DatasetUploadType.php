<?php

declare(strict_types=1);

namespace App\Dataset;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Dataset\Dto\DatasetUploadDto;


final class DatasetUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Dataset name',
                'attr' => ['placeholder' => 'e.g. Warehouse July batch'],
            ])
            ->add('file', FileType::class, [
                'label' => 'CSV file',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DatasetUploadDto::class,
        ]);
    }
}
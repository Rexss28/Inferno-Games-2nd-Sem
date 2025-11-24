<?php

namespace App\Form;

use App\Entity\GameManagement;
use App\Entity\Stock;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GameManagementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FileType::class, [
                'label' => 'Game Cover (JPEG or PNG file)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',

                        ],
                        'mimeTypesMessage' => 'Please upload a valid JPEG, PNG, or JPG image',
                    ])
                ],
            ])
            ->add('title')
            ->add('description')
            ->add('price')
            ->add('stock', EntityType::class, [
                'class' => Stock::class,
                'choice_label' => function (Stock $stock) {
                    return sprintf('Stock #%d – Available: %d / Total: %d', $stock->getId(), $stock->getAvailableQuantity(), $stock->getTotalQuantity());
                },
                'placeholder' => 'N/A',
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->leftJoin('s.game', 'g')
                        ->where('g.id IS NULL');  // 👈 only show unlinked stocks
                },
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GameManagement::class,
        ]);
    }
}

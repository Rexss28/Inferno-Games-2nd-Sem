<?php

namespace App\Form;

use App\Entity\GameManagement;
use App\Entity\Stock;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Positive;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Type;

class GameManagementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $requireImage = $options['require_image'] ?? true;

        $builder
            ->add('image', FileType::class, [
                'label' => 'Game Cover (JPEG or PNG file)',
                'mapped' => false,
                'required' => $requireImage,
                'help' => 'Maximum file size: 5MB. Allowed formats: JPEG, PNG',
                'constraints' => $requireImage ? [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid JPEG, PNG, or JPG image',
                    ])
                ] : [],
            ])
            ->add('title', TextType::class, [
                'label' => 'Game Title',
                'attr' => [
                    'placeholder' => 'Enter game title',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Game title cannot be empty.',
                    ]),
                    new Length([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'The game title must be at least {{ limit }} characters long.',
                        'maxMessage' => 'The game title cannot exceed {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Enter detailed game description',
                    'rows' => 5,
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Description cannot be empty.',
                    ]),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Please provide a more detailed description (at least {{ limit }} characters).',
                    ]),
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Price',
                'currency' => 'USD', // or your default currency
                'scale' => 2,
                'attr' => [
                    'placeholder' => '0.00',
                    'class' => 'form-control',
                    'min' => '0.01',
                    'step' => '0.01',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Price is required.',
                    ]),
                    new Positive([
                        'message' => 'Price must be greater than 0.',
                    ]),
                    new Type([
                        'type' => 'numeric',
                        'message' => 'Price must be a valid number.',
                    ]),
                ],
            ])
            ->add('stock', EntityType::class, [
                'class' => Stock::class,
                'label' => 'Stock Inventory',
                'choice_label' => function (Stock $stock) {
                    return sprintf('Stock #%d – Available: %d / Total: %d', 
                        $stock->getId(), 
                        $stock->getAvailableQuantity(), 
                        $stock->getTotalQuantity()
                    );
                },
                'placeholder' => 'Select a stock record (optional)',
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                ],
                'help' => 'Only unlinked stock records are shown',
                'query_builder' => function (EntityRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('s')
                        ->leftJoin('s.game', 'g')
                        ->where('g.id IS NULL'); // Only show unlinked stocks
                    
                    // If editing, include the current stock
                    if (isset($options['data']) && $options['data']->getId()) {
                        $currentStock = $options['data']->getStock();
                        if ($currentStock) {
                            $qb->orWhere('s.id = :currentStockId')
                               ->setParameter('currentStockId', $currentStock->getId());
                        }
                    }
                    
                    return $qb->orderBy('s.id', 'ASC');
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GameManagement::class,
            'require_image' => true, // Default requires image for new games
        ]);
        
        $resolver->setAllowedTypes('require_image', 'bool');
    }
}
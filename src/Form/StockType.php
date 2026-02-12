<?php

namespace App\Form;

use App\Entity\Stock;
use App\Entity\GameManagement;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Positive;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('availableQuantity', IntegerType::class, [
                'label' => 'Available Quantity',
                'attr' => [
                    'min' => 0,
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Available quantity cannot be empty.']),
                    new PositiveOrZero(['message' => 'Available quantity must be zero or positive.']),
                ],
            ])
            ->add('totalQuantity', IntegerType::class, [
                'label' => 'Total Quantity',
                'attr' => [
                    'min' => 1,
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Total quantity is required.']),
                    new Positive(['message' => 'Total quantity must be a positive number.']),
                ],
            ])
            ->add('game', EntityType::class, [
                'class' => GameManagement::class,
                'label' => 'Linked Game (Optional)',
                'choice_label' => 'title',
                'required' => false,
                'placeholder' => 'Select a game (optional)',
                'attr' => ['class' => 'form-select'],
                'help' => 'Only games without existing stock are shown',
                'query_builder' => function (EntityRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('g')
                        ->leftJoin('g.stock', 's')
                        ->where('s IS NULL');
                    
                    // If editing, include the current game
                    if (isset($options['data']) && $options['data']->getId()) {
                        $currentGame = $options['data']->getGame();
                        if ($currentGame) {
                            $qb->orWhere('g.id = :currentGameId')
                               ->setParameter('currentGameId', $currentGame->getId());
                        }
                    }
                    
                    return $qb->orderBy('g.title', 'ASC');
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stock::class,
        ]);
    }
}
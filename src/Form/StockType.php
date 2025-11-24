<?php

namespace App\Form;

use App\Entity\Stock;
use App\Entity\GameManagement;
// use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
            ->add(child: 'availableQuantity')
            ->add('totalQuantity')
            // ->add('status', ChoiceType::class, [
            //     'choices' => [
            //         'In Stock' => 'In Stock',
            //         'Low Stock' => 'Low Stock',
            //         'Out of Stock' => 'Out of Stock',
            //         'Discontinued' => 'Discontinued',
            //         'Preorder' => 'Preorder',
            //     ],
            //     'label' => 'Stock Status',
            //     'attr' => ['class' => 'form-control'],
            //     ])
             ->add('game', EntityType::class, [
                'class' => GameManagement::class,
                'choice_label' => 'title',
                'required' => false,
                'placeholder' => 'N/A',
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return $er->createQueryBuilder('g')
                        ->leftJoin('g.stock', 's')
                        ->where('s IS NULL OR s = :currentStock')
                        ->setParameter('currentStock', $options['data']->getId() ?? 0);
                },
            ]);


        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stock::class,
        ]);
    }
}

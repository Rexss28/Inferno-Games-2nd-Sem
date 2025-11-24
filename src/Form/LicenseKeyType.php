<?php

namespace App\Form;

use App\Entity\LicenseKey;
use App\Entity\GameManagement;
use App\Entity\Order;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LicenseKeyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code')
            // ->add('status')
            ->add('game', EntityType::class, [
                'class' => GameManagement::class,
                'choice_label' => 'title',
                'placeholder' => 'Select a game',
            ])
            ->add('order', EntityType::class, [
                'class' => Order::class,
                'choice_label' => 'orderNumber',
                'placeholder' => 'N/A', // 👈 Adds a “no selection” option
                'required' => false,     // 👈 Allows it to be left empty
            ])

        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LicenseKey::class,
        ]);
    }
}

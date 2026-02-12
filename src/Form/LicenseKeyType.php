<?php

namespace App\Form;

use App\Entity\LicenseKey;
use App\Entity\GameManagement;
use App\Entity\Order;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LicenseKeyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'License Key Code',
                'attr' => [
                    'placeholder' => 'Enter license key (e.g., XXXXX-XXXXX-XXXXX)',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'License key code cannot be empty.']),
                    new Length([
                        'min' => 5,
                        'max' => 100,
                        'minMessage' => 'License key must be at least {{ limit }} characters long.',
                        'maxMessage' => 'License key cannot exceed {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('game', EntityType::class, [
                'class' => GameManagement::class,
                'label' => 'Associated Game',
                'choice_label' => 'title',
                'placeholder' => 'Select a game',
                'required' => true,
                'attr' => ['class' => 'form-select'],
                'help' => 'Choose which game this license key is for',
            ])
            ->add('order', EntityType::class, [
                'class' => Order::class,
                'label' => 'Linked Order (Optional)',
                'choice_label' => function(Order $order) {
                    return sprintf('Order #%s - %s', $order->getOrderNumber(), $order->getStatus());
                },
                'placeholder' => 'No order linked',
                'required' => false,
                'attr' => ['class' => 'form-select'],
                'help' => 'Link to an order if this key has been sold/reserved',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LicenseKey::class,
        ]);
    }
}
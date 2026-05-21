<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ContactMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'empty_data' => '',
                'attr' => ['class' => 'ig-input', 'maxlength' => 120],
                'label_attr' => ['class' => 'ig-label'],
                'row_attr' => ['class' => 'ig-field-row'],
                'constraints' => [
                    new NotBlank(message: 'Please enter your name.'),
                    new Length(max: 120),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'empty_data' => '',
                'attr' => ['class' => 'ig-input', 'autocomplete' => 'email'],
                'label_attr' => ['class' => 'ig-label'],
                'row_attr' => ['class' => 'ig-field-row'],
                'constraints' => [
                    new NotBlank(message: 'Please enter your email.'),
                    new Email(message: 'Please enter a valid email address.'),
                ],
            ])
            ->add('subject', ChoiceType::class, [
                'label' => 'Topic',
                'placeholder' => 'Choose a topic',
                'choices' => [
                    'General question' => 'general',
                    'Store & orders' => 'orders',
                    'Technical / client help' => 'technical',
                    'Partnerships & press' => 'partners',
                    'Platform Technology — course / grading' => 'coursework',
                ],
                'attr' => ['class' => 'ig-input ig-input--select'],
                'label_attr' => ['class' => 'ig-label'],
                'row_attr' => ['class' => 'ig-field-row'],
                'constraints' => [
                    new NotBlank(message: 'Please choose a topic.'),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'empty_data' => '',
                'attr' => ['class' => 'ig-input ig-input--textarea', 'rows' => 6],
                'label_attr' => ['class' => 'ig-label'],
                'row_attr' => ['class' => 'ig-field-row'],
                'constraints' => [
                    new NotBlank(message: 'Please enter a message.'),
                    new Length(min: 10, max: 4000, minMessage: 'Please write at least {{ limit }} characters.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}

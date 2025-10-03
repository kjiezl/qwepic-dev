<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Role;
use App\Entity\User;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'label_attr' => [
                    'class' => 'block font-semibold text-sm text-space-cadet mb-2 font-sans',
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-3 border-2 border-slate-gray rounded-lg bg-anti-flash-white text-base text-space-cadet transition duration-300 focus:outline-none focus:border-vivid-sky-blue focus:ring-2 focus:ring-vivid-sky-blue/10 font-sans',
                    'placeholder' => 'Enter your email',
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'mapped' => false,
                'label_attr' => [
                    'class' => 'block font-semibold text-sm text-space-cadet mb-2 font-sans',
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-3 border-2 border-slate-gray rounded-lg bg-anti-flash-white text-base text-space-cadet transition duration-300 focus:outline-none focus:border-vivid-sky-blue focus:ring-2 focus:ring-vivid-sky-blue/10 font-sans',
                    'placeholder' => 'Create a strong password',
                ],
            ])
            ->add('role', EntityType::class, [
                'label' => 'Role',
                'label_attr' => [
                    'class' => 'block font-semibold text-sm text-space-cadet mb-2 font-sans',
                ],
                'class' => Role::class,
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'w-full px-4 py-3 border-2 border-slate-gray rounded-lg bg-anti-flash-white text-base text-space-cadet transition duration-300 focus:outline-none cursor-pointer focus:ring-1 font-sans',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
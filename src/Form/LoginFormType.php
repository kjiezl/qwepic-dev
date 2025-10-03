<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('_username', EmailType::class, [
                'label' => 'Email',
                'label_attr' => [
                    'class' => 'block font-inter font-semibold text-sm text-space-cadet mb-2',
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-3 border-2 border-slate-gray rounded-lg bg-anti-flash-white font-inter text-base text-space-cadet transition duration-300 focus:outline-none focus:border-vivid-sky-blue focus:ring-2 focus:ring-vivid-sky-blue/10',
                    'placeholder' => 'Enter your email',
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'label_attr' => [
                    'class' => 'block font-inter font-semibold text-sm text-space-cadet mb-2',
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-3 border-2 border-slate-gray rounded-lg bg-anti-flash-white font-inter text-base text-space-cadet transition duration-300 focus:outline-none focus:border-vivid-sky-blue focus:ring-2 focus:ring-vivid-sky-blue/10',
                    'placeholder' => 'Enter your password',
                ],
            ])
            ->add('remember_me', CheckboxType::class, [
                'label'    => 'Remember me',
                'required' => false,
                'label_attr' => [
                    'class' => 'flex items-center font-inter text-sm text-slate-gray cursor-pointer',
                ],
                'attr' => [
                    'class' => 'w-4 h-4 mr-3 accent-accent-coral',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // No data_class for login form
        ]);
    }
}
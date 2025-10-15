<?php

namespace App\Form;

use App\Entity\Album;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class AlbumWithPhotosType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('title', TextType::class, [
                'label' => 'Album Name',
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-vivid-sky-blue focus:border-vivid-sky-blue',
                    'placeholder' => 'Enter album name...',
                ],
                'constraints' => [
                    new NotBlank(message: 'Album name is required'),
                    new Length(max: 100, maxMessage: 'Album name cannot be longer than {{ limit }} characters')
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-vivid-sky-blue focus:border-vivid-sky-blue',
                    'placeholder' => 'Describe your album...',
                    'rows' => 4,
                ],
            ])
            ->add('isPublic', ChoiceType::class, [
                'label' => 'Privacy Setting',
                'choices' => [
                    'Public - Visible to everyone' => true,
                    'Private - Only visible to you' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'data' => true, // Default to public
                'attr' => [
                    'class' => 'space-y-2',
                ],
            ])
            ->add('photos', FileType::class, [
                'label' => 'Upload Photos',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'block w-full text-sm text-slate-gray file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-vivid-sky-blue file:text-white hover:file:bg-blue-500',
                    'multiple' => true
                ],
                'constraints' => [
                    new All([
                        new File(
                            maxSize: '10M',
                            mimeTypes: [
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'image/webp',
                            ],
                            mimeTypesMessage: 'Please upload valid image files (JPEG, PNG, GIF, or WebP)'
                        )
                    ])
                ],
            ])
            ->add('photoTitles', CollectionType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'entry_type' => TextType::class,
                'entry_options' => [
                    'required' => false,
                    'attr' => [
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-vivid-sky-blue',
                        'placeholder' => 'Photo title...'
                    ]
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
            ])
            ->add('photoDescriptions', CollectionType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'entry_type' => TextareaType::class,
                'entry_options' => [
                    'required' => false,
                    'attr' => [
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-vivid-sky-blue',
                        'placeholder' => 'Photo description...',
                        'rows' => 2
                    ]
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Album::class,
            'is_edit' => false,
        ]);
    }
}

<?php

namespace App\Form;

use App\Entity\Photo;
use App\Entity\Album;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Security\Core\Security;

class PhotoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;
        $user = $options['user'] ?? null;
        $standaloneOnly = $options['standalone_only'] ?? false;

        $builder
            ->add('src', FileType::class, [
                'label' => 'Photo File',
                'mapped' => false,
                'required' => !$isEdit,
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'block w-full text-sm text-slate-gray file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-vivid-sky-blue file:text-white hover:file:bg-blue-500'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, GIF, or WebP)',
                    ])
                ],
            ])
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-vivid-sky-blue focus:border-vivid-sky-blue',
                    'placeholder' => 'Enter photo title'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Title is required']),
                    new Length(['max' => 100, 'maxMessage' => 'Title cannot be longer than {{ limit }} characters'])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-vivid-sky-blue focus:border-vivid-sky-blue',
                    'placeholder' => 'Enter photo description (optional)',
                    'rows' => 4
                ]
            ])
            ->add('album', EntityType::class, [
                'class' => Album::class,
                'choice_label' => 'title',
                'label' => $standaloneOnly ? 'Featured Photo (No Album)' : 'Album (Optional)',
                'required' => false,
                'placeholder' => $standaloneOnly ? 'This will be a featured photo' : 'Select an album',
                'disabled' => $standaloneOnly, // Disable album selection for standalone photos
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-vivid-sky-blue focus:border-vivid-sky-blue' . ($standaloneOnly ? ' bg-gray-100 text-gray-500' : '')
                ],
                'query_builder' => function ($repository) use ($user, $standaloneOnly) {
                    if ($standaloneOnly) {
                        return $repository->createQueryBuilder('a')->where('1 = 0'); // No albums for standalone
                    }
                    if ($user instanceof User) {
                        return $repository->createQueryBuilder('a')
                            ->where('a.photographer = :user')
                            ->setParameter('user', $user)
                            ->orderBy('a.title', 'ASC');
                    }
                    return $repository->createQueryBuilder('a')->where('1 = 0'); // Return no results if no user
                }
            ])
            ->add('isPublic', CheckboxType::class, [
                'label' => 'Make this photo public',
                'required' => false,
                'data' => true, // Default to public
                'attr' => [
                    'class' => 'w-4 h-4 text-vivid-sky-blue bg-gray-100 border-gray-300 rounded focus:ring-vivid-sky-blue focus:ring-2'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Photo::class,
            'is_edit' => false,
            'user' => null,
            'standalone_only' => false,
        ]);
    }
}
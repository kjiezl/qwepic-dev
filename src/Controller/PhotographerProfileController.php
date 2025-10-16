<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

class PhotographerProfileController extends AbstractController
{
    #[Route('/photographer/{id}', name: 'photographer_profile')]
    public function view(int $id, EntityManagerInterface $em): Response
    {
        $photographer = $em->getRepository(User::class)->find($id);
        if (!$photographer) {
            throw $this->createNotFoundException('Photographer not found.');
        }

        // Check if photographer is banned or suspended
        $currentUser = $this->getUser();
        $isOwner = $currentUser && $currentUser === $photographer;
        $isBanned = $photographer->getStatus() === 'banned';
        $isSuspended = $photographer->getStatus() === 'suspended';

        // If user is banned and current user is not the owner or admin, show banned state
        if ($isBanned && !$isOwner && (!$currentUser || !in_array('ROLE_ADMIN', $currentUser->getRoles()))) {
            return $this->render('photographer_profile/banned.html.twig', [
                'photographer' => $photographer,
            ]);
        }

        // Load public albums and photos for portfolio display (only if not banned or user is owner/admin)
        $albums = [];
        $photos = [];
        
        if (!$isBanned || $isOwner || ($currentUser && in_array('ROLE_ADMIN', $currentUser->getRoles()))) {
            $albums = $photographer->getAlbums()->filter(function($album) {
                return $album->isPublic() && $album->getStatus() === 'approved';
            })->toArray();

            // Get public standalone photos for featured portfolio (limit to best 6)
            $allPhotos = $photographer->getPhotos();
            $filteredPhotos = $allPhotos->filter(function($photo) {
                return $photo->isPublic() && $photo->getAlbum() === null && $photo->getStatus() === 'approved';
            });

            $photos = $filteredPhotos->slice(0, 6);
        }

        return $this->render('photographer_profile/index.html.twig', [
            'photographer' => $photographer,
            'albums' => $albums,
            'photos' => $photos,
            'isBanned' => $isBanned,
            'isSuspended' => $isSuspended,
            'isOwner' => $isOwner,
        ]);
    }

    #[Route('/photographer/{id}/edit', name: 'app_photographer_edit')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(int $id, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $photographer = $em->getRepository(User::class)->find($id);

        if (!$photographer) {
            throw $this->createNotFoundException('Photographer not found.');
        }

        // Ensure only the profile owner can edit their profile
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser || $currentUser->getId() !== $photographer->getId()) {
            throw $this->createAccessDeniedException('You can only edit your own profile.');
        }

        $form = $this->createForm(ProfileType::class, $photographer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle avatar upload
            $avatarFile = $form->get('avatar')->getData();

            if ($avatarFile) {
                $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$avatarFile->guessExtension();

                try {
                    $avatarFile->move(
                        $this->getParameter('avatars_directory'),
                        $newFilename
                    );

                    // Remove old avatar if it exists
                    if ($photographer->getAvatar()) {
                        $oldAvatarPath = $this->getParameter('avatars_directory').'/'.$photographer->getAvatar();
                        if (file_exists($oldAvatarPath)) {
                            unlink($oldAvatarPath);
                        }
                    }

                    $photographer->setAvatar($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload avatar image.');
                }
            }

            // Update the updatedAt timestamp
            $photographer->setUpdatedAt(new \DateTimeImmutable());

            $em->flush();

            // $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('photographer_profile', ['id' => $id]);
        }

        return $this->render('photographer_profile/edit.html.twig', [
            'photographer' => $photographer,
            'form' => $form->createView(),
        ]);
    }
}
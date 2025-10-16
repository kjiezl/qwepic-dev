<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Photo;
use App\Entity\User;
use App\Form\AlbumType;
use App\Form\AlbumWithPhotosType;
use App\Repository\PhotoRepository;
use App\Service\PhotoUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/album')]
final class AlbumController extends AbstractController
{
    #[Route(name: 'app_album_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to view albums.');
        }

        $albums = $entityManager
            ->getRepository(Album::class)
            ->findBy(['photographer' => $user]);

        return $this->render('album/index.html.twig', [
            'albums' => $albums,
        ]);
    }

    #[Route('/new', name: 'app_album_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager, PhotoUploadService $uploadService): Response
    {
        $user = $this->getUser();
        
        // Check if user is suspended or banned
        if ($user instanceof User && in_array($user->getStatus(), ['suspended', 'banned'])) {
            $statusMessage = $user->getStatus() === 'banned' ? 'banned' : 'suspended';
            $this->addFlash('error', "Your account is {$statusMessage}. You cannot create new albums.");
            return $this->redirectToRoute('app_home');
        }
        
        $album = new Album();
        
        if ($user instanceof User) {
            $album->setPhotographer($user);
        }

        $form = $this->createForm(AlbumWithPhotosType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($album);
            $entityManager->flush();

            // Handle photo uploads
            $uploadedFiles = $form->get('photos')->getData();
            $photoTitles = $form->get('photoTitles')->getData() ?? [];
            $photoDescriptions = $form->get('photoDescriptions')->getData() ?? [];

            if ($uploadedFiles) {
                foreach ($uploadedFiles as $index => $uploadedFile) {
                    if ($uploadedFile instanceof UploadedFile) {
                        // Validate file
                        $errors = $uploadService->validateImageFile($uploadedFile);
                        if (empty($errors)) {
                            // Create photo entity
                            $photo = new Photo();
                            $photo->setPhotographer($user);
                            $photo->setAlbum($album);
                            $photo->setTitle($photoTitles[$index] ?? 'Photo ' . ($index + 1));
                            $photo->setDescription($photoDescriptions[$index] ?? null);
                            $photo->setIsPublic($album->isPublic());

                            // Upload file and generate thumbnails
                            $uploadResult = $uploadService->uploadPhoto($uploadedFile, $photo->getTitle());
                            $photo->setSrc($uploadResult['filename']);
                            $photo->setThumbnails($uploadResult['thumbnails']);

                            $entityManager->persist($photo);
                        } else {
                            foreach ($errors as $error) {
                                $this->addFlash('error', "Photo " . ($index + 1) . ": " . $error);
                            }
                        }
                    }
                }
                $entityManager->flush();
            }

            // $this->addFlash('success', 'Album created successfully!');
            return $this->redirectToRoute('app_album_show', ['id' => $album->getId()]);
        }

        return $this->render('album/new.html.twig', [
            'album' => $album,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_album_show', methods: ['GET'])]
    public function show(Album $album, PhotoRepository $photoRepository): Response
    {
        $user = $this->getUser();
        $isOwner = $user && $album->getPhotographer() === $user;
        
        // Load photos for this album - show all photos if owner, only approved if public view
        $photos = $photoRepository->findPhotosInAlbum($album, !$isOwner);
        
        return $this->render('album/show.html.twig', [
            'album' => $album,
            'photos' => $photos,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_album_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Album $album, EntityManagerInterface $entityManager, PhotoUploadService $uploadService): Response
    {
        // Check if user owns the album
        if ($album->getPhotographer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only edit your own albums.');
        }

        $form = $this->createForm(AlbumWithPhotosType::class, $album, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Handle new photo uploads
            $uploadedFiles = $form->get('photos')->getData();
            $photoTitles = $form->get('photoTitles')->getData() ?? [];
            $photoDescriptions = $form->get('photoDescriptions')->getData() ?? [];

            if ($uploadedFiles) {
                // Check photo limit (100 photos per album)
                $currentPhotoCount = $album->getPhotos()->count();
                $newPhotoCount = count($uploadedFiles);
                
                if ($currentPhotoCount + $newPhotoCount > 100) {
                    $this->addFlash('error', "Cannot upload {$newPhotoCount} photos. Album limit is 100 photos. Current: {$currentPhotoCount}");
                    return $this->redirectToRoute('app_album_edit', ['id' => $album->getId()]);
                }
                foreach ($uploadedFiles as $index => $uploadedFile) {
                    if ($uploadedFile instanceof UploadedFile) {
                        // Validate file
                        $errors = $uploadService->validateImageFile($uploadedFile);
                        if (empty($errors)) {
                            // Create photo entity
                            $photo = new Photo();
                            $photo->setPhotographer($this->getUser());
                            $photo->setAlbum($album);
                            $photo->setTitle($photoTitles[$index] ?? 'Photo ' . ($index + 1));
                            $photo->setDescription($photoDescriptions[$index] ?? null);
                            $photo->setIsPublic($album->isPublic());

                            // Upload file and generate thumbnails
                            $uploadResult = $uploadService->uploadPhoto($uploadedFile, $photo->getTitle());
                            $photo->setSrc($uploadResult['filename']);
                            $photo->setThumbnails($uploadResult['thumbnails']);

                            $entityManager->persist($photo);
                        } else {
                            foreach ($errors as $error) {
                                $this->addFlash('error', "Photo " . ($index + 1) . ": " . $error);
                            }
                        }
                    }
                }
                $entityManager->flush();
            }

            // $this->addFlash('success', 'Album updated successfully!');
            return $this->redirectToRoute('app_album_show', ['id' => $album->getId()]);
        }

        return $this->render('album/edit.html.twig', [
            'album' => $album,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_album_delete', methods: ['POST'])]
    public function delete(Request $request, Album $album, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$album->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($album);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_album_index', [], Response::HTTP_SEE_OTHER);
    }
}

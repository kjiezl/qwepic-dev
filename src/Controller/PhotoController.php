<?php

namespace App\Controller;

use App\Entity\Photo;
use App\Entity\User;
use App\Form\PhotoType;
use App\Service\PhotoUploadService;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/photo')]
final class PhotoController extends AbstractController
{

    #[Route('/photographer/{id}/photos', name: 'app_photographer_photos', methods: ['GET'])]
    public function photographerPhotos(User $photographer, PhotoRepository $photoRepository): Response
    {
        $user = $this->getUser();
        $publicOnly = !$user || $photographer !== $user;
        
        $photos = $photoRepository->findByPhotographer($photographer, $publicOnly);

        return $this->render('photo/photographer_photos.html.twig', [
            'photographer' => $photographer,
            'photos' => $photos,
        ]);
    }

    #[Route('/standalone/new', name: 'app_photo_standalone_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function newStandalone(Request $request, EntityManagerInterface $entityManager, PhotoUploadService $uploadService): Response
    {
        $user = $this->getUser();
        
        // Check if user is suspended or banned
        if ($user instanceof User && in_array($user->getStatus(), ['suspended', 'banned'])) {
            $statusMessage = $user->getStatus() === 'banned' ? 'banned' : 'suspended';
            $this->addFlash('error', "Your account is {$statusMessage}. You cannot upload new photos.");
            return $this->redirectToRoute('app_home');
        }
        
        $photo = new Photo();
        
        if ($user instanceof User) {
            $photo->setPhotographer($user);
            // Force standalone (no album)
            $photo->setAlbum(null);
        }

        $form = $this->createForm(PhotoType::class, $photo, [
            'user' => $user,
            'standalone_only' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // For standalone photos, ensure album is explicitly set to null
            if (isset($options['standalone_only']) && $options['standalone_only']) {
                $photo->setAlbum(null);
            }

            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('src')->getData();

            if ($uploadedFile) {
                // Validate file
                $errors = $uploadService->validateImageFile($uploadedFile);
                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error);
                    }
                    return $this->render('photo/standalone_new.html.twig', [
                        'photo' => $photo,
                        'form' => $form,
                    ]);
                }

                // Upload file and generate thumbnails
                $uploadResult = $uploadService->uploadPhoto($uploadedFile, $photo->getTitle());
                $photo->setSrc($uploadResult['filename']);
                $photo->setThumbnails($uploadResult['thumbnails']);
            }

            $entityManager->persist($photo);
            $entityManager->flush();

            // $this->addFlash('success', 'Featured photo uploaded successfully!');
            return $this->redirectToRoute('app_photo_show', ['id' => $photo->getId()]);
        }

        return $this->render('photo/standalone_new.html.twig', [
            'photo' => $photo,
            'form' => $form,
        ]);
    }

    #[Route('/new', name: 'app_photo_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager, PhotoUploadService $uploadService): Response
    {
        $user = $this->getUser();
        
        // Check if user is suspended or banned
        if ($user instanceof User && in_array($user->getStatus(), ['suspended', 'banned'])) {
            $statusMessage = $user->getStatus() === 'banned' ? 'banned' : 'suspended';
            $this->addFlash('error', "Your account is {$statusMessage}. You cannot upload new photos.");
            return $this->redirectToRoute('app_home');
        }
        
        $photo = new Photo();
        
        if ($user instanceof User) {
            $photo->setPhotographer($user);
        }

        $form = $this->createForm(PhotoType::class, $photo, [
            'user' => $user
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('src')->getData();

            if ($uploadedFile) {
                // Validate file
                $errors = $uploadService->validateImageFile($uploadedFile);
                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error);
                    }
                    return $this->render('photo/new.html.twig', [
                        'photo' => $photo,
                        'form' => $form,
                    ]);
                }

                // Upload file and generate thumbnails
                $uploadResult = $uploadService->uploadPhoto($uploadedFile, $photo->getTitle());
                $photo->setSrc($uploadResult['filename']);
                $photo->setThumbnails($uploadResult['thumbnails']);
            }

            $entityManager->persist($photo);
            $entityManager->flush();

            // $this->addFlash('success', 'Photo uploaded successfully!');
            return $this->redirectToRoute('app_photo_show', ['id' => $photo->getId()]);
        }

        return $this->render('photo/new.html.twig', [
            'photo' => $photo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_photo_show', methods: ['GET'])]
    public function show(Photo $photo): Response
    {
        // Check if photo is public or user owns it
        $user = $this->getUser();
        $isOwner = $user && $photo->getPhotographer() === $user;
        
        if (!$photo->isPublic() && !$isOwner) {
            throw $this->createAccessDeniedException('This photo is private.');
        }
        
        // Check if photo is approved for non-owners
        if (!$isOwner && $photo->getStatus() !== 'approved') {
            throw $this->createNotFoundException('Photo not found.');
        }

        return $this->render('photo/show.html.twig', [
            'photo' => $photo,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_photo_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Photo $photo, EntityManagerInterface $entityManager, PhotoUploadService $uploadService): Response
    {
        // Check if user owns the photo
        if ($photo->getPhotographer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only edit your own photos.');
        }

        $form = $this->createForm(PhotoType::class, $photo, [
            'is_edit' => true,
            'user' => $this->getUser()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('src')->getData();

            if ($uploadedFile) {
                // Validate new file
                $errors = $uploadService->validateImageFile($uploadedFile);
                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error);
                    }
                    return $this->render('photo/edit.html.twig', [
                        'photo' => $photo,
                        'form' => $form,
                    ]);
                }

                // Delete old files
                $uploadService->deletePhoto($photo->getSrc(), $photo->getThumbnails());

                // Upload new file
                $uploadResult = $uploadService->uploadPhoto($uploadedFile, $photo->getTitle());
                $photo->setSrc($uploadResult['filename']);
                $photo->setThumbnails($uploadResult['thumbnails']);
            }

            $entityManager->flush();

            // $this->addFlash('success', 'Photo updated successfully!');
            return $this->redirectToRoute('app_photo_show', ['id' => $photo->getId()]);
        }

        return $this->render('photo/edit.html.twig', [
            'photo' => $photo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_photo_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Photo $photo, EntityManagerInterface $entityManager, PhotoUploadService $uploadService): Response
    {
        // Check if user owns the photo
        if ($photo->getPhotographer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only delete your own photos.');
        }

        if ($this->isCsrfTokenValid('delete'.$photo->getId(), $request->getPayload()->getString('_token'))) {
            // Delete files
            $uploadService->deletePhoto($photo->getSrc(), $photo->getThumbnails());
            
            $entityManager->remove($photo);
            $entityManager->flush();

            // $this->addFlash('success', 'Photo deleted successfully!');
        }

        return $this->redirectToRoute('app_home');
    }

    // API Endpoints for mobile integration
    #[Route('/api/photos', name: 'api_photo_list', methods: ['GET'])]
    public function apiList(PhotoRepository $photoRepository, Request $request): JsonResponse
    {
        $limit = min($request->query->getInt('limit', 20), 50);
        $offset = $request->query->getInt('offset', 0);
        
        $photos = $photoRepository->findPublicPhotos($limit, $offset);
        
        $data = [];
        foreach ($photos as $photo) {
            $data[] = [
                'id' => $photo->getId(),
                'title' => $photo->getTitle(),
                'description' => $photo->getDescription(),
                'src' => $photo->getSrc(),
                'thumbnails' => $photo->getThumbnails(),
                'isPublic' => $photo->isPublic(),
                'createdAt' => $photo->getCreatedAt()->format('Y-m-d H:i:s'),
                'photographer' => [
                    'id' => $photo->getPhotographer()->getId(),
                    'email' => $photo->getPhotographer()->getEmail(),
                ]
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/api/photos/{id}', name: 'api_photo_show', methods: ['GET'])]
    public function apiShow(Photo $photo): JsonResponse
    {
        // Check if photo is public and approved
        if (!$photo->isPublic() || $photo->getStatus() !== 'approved') {
            return new JsonResponse(['error' => 'Photo not found'], 404);
        }

        return new JsonResponse([
            'id' => $photo->getId(),
            'title' => $photo->getTitle(),
            'description' => $photo->getDescription(),
            'src' => $photo->getSrc(),
            'thumbnails' => $photo->getThumbnails(),
            'tags' => $photo->getTags(),
            'isPublic' => $photo->isPublic(),
            'createdAt' => $photo->getCreatedAt()->format('Y-m-d H:i:s'),
            'photographer' => [
                'id' => $photo->getPhotographer()->getId(),
                'email' => $photo->getPhotographer()->getEmail(),
            ]
        ]);
    }

    #[Route('/{id}/edit-ajax', name: 'api_photo_edit', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function apiEdit(Photo $photo, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Check if user owns the photo
        if ($photo->getPhotographer() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['title']) || empty(trim($data['title']))) {
            return new JsonResponse(['error' => 'Title is required'], 400);
        }

        $photo->setTitle(trim($data['title']));
        $photo->setDescription(trim($data['description'] ?? ''));
        
        // Handle tags
        if (isset($data['tags'])) {
            $tags = array_map('trim', explode(',', $data['tags']));
            $tags = array_filter($tags); // Remove empty tags
            $photo->setTags($tags);
        }

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Photo updated successfully',
            'photo' => [
                'id' => $photo->getId(),
                'title' => $photo->getTitle(),
                'description' => $photo->getDescription()
            ]
        ]);
    }

    #[Route('/{id}/delete-ajax', name: 'api_photo_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function apiDelete(Photo $photo, EntityManagerInterface $entityManager, PhotoUploadService $uploadService): JsonResponse
    {
        // Check if user owns the photo
        if ($photo->getPhotographer() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        // Delete files
        $uploadService->deletePhoto($photo->getSrc(), $photo->getThumbnails());
        
        $entityManager->remove($photo);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Photo deleted successfully'
        ]);
    }

    #[Route('/bulk-delete', name: 'api_photos_bulk_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function bulkDelete(Request $request, EntityManagerInterface $entityManager, PhotoUploadService $uploadService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['photoIds']) || !is_array($data['photoIds'])) {
            return new JsonResponse(['error' => 'Photo IDs are required'], 400);
        }

        $photoIds = $data['photoIds'];
        $deletedCount = 0;
        $errors = [];

        foreach ($photoIds as $photoId) {
            try {
                $photo = $entityManager->getRepository(Photo::class)->find($photoId);
                
                if (!$photo) {
                    $errors[] = "Photo with ID {$photoId} not found";
                    continue;
                }

                // Check if user owns the photo
                if ($photo->getPhotographer() !== $this->getUser()) {
                    $errors[] = "Access denied for photo ID {$photoId}";
                    continue;
                }

                // Delete files
                $uploadService->deletePhoto($photo->getSrc(), $photo->getThumbnails());
                
                $entityManager->remove($photo);
                $deletedCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to delete photo ID {$photoId}: " . $e->getMessage();
            }
        }

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => "Successfully deleted {$deletedCount} photos",
            'deletedCount' => $deletedCount,
            'errors' => $errors
        ]);
    }

    #[Route('/regenerate-thumbnails/{id}', name: 'app_photo_regenerate_thumbnails', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function regenerateThumbnails(Photo $photo, EntityManagerInterface $entityManager, PhotoUploadService $uploadService): JsonResponse
    {
        // Check if user owns the photo
        if ($photo->getPhotographer() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        // Check if photo has original file
        if (!$photo->getSrc()) {
            return new JsonResponse(['error' => 'Photo has no source file'], 400);
        }

        try {
            // Regenerate thumbnails
            $thumbnails = $uploadService->regenerateThumbnailsForPhoto($photo->getSrc(), $photo->getTitle());

            // Update photo with new thumbnails
            $photo->setThumbnails($thumbnails);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Thumbnails regenerated successfully',
                'thumbnails' => $thumbnails
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to regenerate thumbnails: ' . $e->getMessage()], 500);
        }
    }
}
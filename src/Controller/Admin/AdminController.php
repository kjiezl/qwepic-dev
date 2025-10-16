<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use App\Entity\Photo;
use App\Entity\User;
use App\Repository\AlbumRepository;
use App\Repository\PhotoRepository;
use App\Repository\UserRepository;
use App\Service\PhotoUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private PhotoRepository $photoRepository,
        private AlbumRepository $albumRepository,
        private PhotoUploadService $photoUploadService
    ) {}

    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        // Get dashboard statistics
        $userCount = $this->userRepository->count([]);
        $photoCount = $this->photoRepository->count([]);
        $albumCount = $this->albumRepository->count([]);
        
        // Get recent activity
        $recentUsers = $this->userRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $recentPhotos = $this->photoRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $recentAlbums = $this->albumRepository->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'userCount' => $userCount,
            'photoCount' => $photoCount,
            'albumCount' => $albumCount,
            'recentUsers' => $recentUsers,
            'recentPhotos' => $recentPhotos,
            'recentAlbums' => $recentAlbums,
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        
        $queryBuilder = $this->userRepository->createQueryBuilder('u')
            ->leftJoin('u.role', 'r')
            ->addSelect('r');
            
        if ($search) {
            $queryBuilder->andWhere('u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        if ($status) {
            $queryBuilder->andWhere('u.status = :status')
                ->setParameter('status', $status);
        }
        
        $users = $queryBuilder->orderBy('u.createdAt', 'DESC')->getQuery()->getResult();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'search' => $search,
            'status' => $status,
        ]);
    }

    #[Route('/users/{id}/status', name: 'admin_user_status', methods: ['POST'])]
    public function updateUserStatus(User $user, Request $request): JsonResponse
    {
        $status = $request->request->get('status');
        
        if (!in_array($status, ['active', 'suspended', 'banned'])) {
            return new JsonResponse(['error' => 'Invalid status'], 400);
        }
        
        $user->setStatus($status);
        $this->entityManager->flush();
        
        return new JsonResponse(['success' => true, 'status' => $status]);
    }

    #[Route('/content', name: 'admin_content')]
    public function content(Request $request): Response
    {
        $type = $request->query->get('type', 'photos');
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        
        if ($type === 'albums') {
            $queryBuilder = $this->albumRepository->createQueryBuilder('a')
                ->leftJoin('a.photographer', 'u')
                ->addSelect('u');
                
            if ($search) {
                $queryBuilder->andWhere('a.title LIKE :search OR u.email LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }
            
            if ($status) {
                $queryBuilder->andWhere('a.status = :status')
                    ->setParameter('status', $status);
            }
            
            $content = $queryBuilder->orderBy('a.createdAt', 'DESC')->getQuery()->getResult();
        } else {
            $queryBuilder = $this->photoRepository->createQueryBuilder('p')
                ->leftJoin('p.photographer', 'u')
                ->leftJoin('p.album', 'a')
                ->addSelect('u', 'a');
                
            if ($search) {
                $queryBuilder->andWhere('p.title LIKE :search OR u.email LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }
            
            if ($status) {
                $queryBuilder->andWhere('p.status = :status')
                    ->setParameter('status', $status);
            }
            
            $content = $queryBuilder->orderBy('p.createdAt', 'DESC')->getQuery()->getResult();
        }

        return $this->render('admin/content.html.twig', [
            'content' => $content,
            'type' => $type,
            'search' => $search,
            'status' => $status,
        ]);
    }

    #[Route('/content/photo/{id}/status', name: 'admin_photo_status', methods: ['POST'])]
    public function updatePhotoStatus(Photo $photo, Request $request): JsonResponse
    {
        $status = $request->request->get('status');
        
        if (!in_array($status, ['approved', 'pending', 'rejected'])) {
            return new JsonResponse(['error' => 'Invalid status'], 400);
        }
        
        $photo->setStatus($status);
        $this->entityManager->flush();
        
        return new JsonResponse(['success' => true, 'status' => $status]);
    }

    #[Route('/content/album/{id}/status', name: 'admin_album_status', methods: ['POST'])]
    public function updateAlbumStatus(Album $album, Request $request): JsonResponse
    {
        $status = $request->request->get('status');
        
        if (!in_array($status, ['approved', 'pending', 'rejected'])) {
            return new JsonResponse(['error' => 'Invalid status'], 400);
        }
        
        $album->setStatus($status);
        $this->entityManager->flush();
        
        return new JsonResponse(['success' => true, 'status' => $status]);
    }

    #[Route('/content/photo/{id}/delete', name: 'admin_photo_delete', methods: ['DELETE'])]
    public function deletePhoto(Photo $photo): JsonResponse
    {
        try {
            // Delete physical files
            $this->photoUploadService->deletePhoto($photo->getSrc(), $photo->getThumbnails());
            
            // Remove from database
            $this->entityManager->remove($photo);
            $this->entityManager->flush();
            
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to delete photo: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/content/album/{id}/delete', name: 'admin_album_delete', methods: ['DELETE'])]
    public function deleteAlbum(Album $album): JsonResponse
    {
        try {
            // Delete all photos in the album first
            $photos = $album->getPhotos();
            foreach ($photos as $photo) {
                // Delete physical files
                $this->photoUploadService->deletePhoto($photo->getSrc(), $photo->getThumbnails());
                // Remove from database
                $this->entityManager->remove($photo);
            }
            
            // Remove the album
            $this->entityManager->remove($album);
            $this->entityManager->flush();
            
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to delete album: ' . $e->getMessage()], 500);
        }
    }
}

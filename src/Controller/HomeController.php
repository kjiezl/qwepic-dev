<?php

namespace App\Controller;

use App\Repository\PhotoRepository;
use App\Repository\AlbumRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(PhotoRepository $photoRepository, AlbumRepository $albumRepository): Response
    {
        // Get public photos for the homepage
        $photos = $photoRepository->findPublicPhotos(20);
        
        // Get featured standalone photos
        $featuredPhotos = $photoRepository->findStandalonePhotos(8);
        
        // Get recent public albums
        $albums = $albumRepository->findPublicAlbums(6);

        return $this->render('home/index.html.twig', [
            'photos' => $photos,
            'featuredPhotos' => $featuredPhotos,
            'albums' => $albums,
        ]);
    }

    #[Route('/feed', name: 'app_photo_feed')]
    public function feed(PhotoRepository $photoRepository): Response
    {
        $photos = $photoRepository->findPublicPhotos(20);

        return $this->render('photo/feed.html.twig', [
            'photos' => $photos,
        ]);
    }

    #[Route('/featured', name: 'app_photo_standalone')]
    public function featured(PhotoRepository $photoRepository): Response
    {
        $photos = $photoRepository->findStandalonePhotos(20);

        return $this->render('photo/standalone.html.twig', [
            'photos' => $photos,
        ]);
    }
}

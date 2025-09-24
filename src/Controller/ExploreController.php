<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ExploreController extends AbstractController
{
    #[Route('/explore', name: 'app_explore')]
    public function index(): Response
    {
        $categories = [
            ['name' => 'All', 'slug' => 'all', 'active' => true],
            ['name' => 'Weddings', 'slug' => 'weddings', 'active' => false],
            ['name' => 'Nature', 'slug' => 'nature', 'active' => false],
            ['name' => 'Events', 'slug' => 'events', 'active' => false],
            ['name' => 'Portraits', 'slug' => 'portraits', 'active' => false],
            ['name' => 'Travel', 'slug' => 'travel', 'active' => false],
        ];

        return $this->render('explore/index.html.twig', [
            'controller_name' => 'ExploreController',
            'categories' => $categories,
        ]);
    }
}

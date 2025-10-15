<?php

namespace App\Command;

use App\Entity\Photo;
use App\Service\PhotoUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-thumbnails',
    description: 'Generate thumbnails for photos that don\'t have them',
)]
class GenerateThumbnailsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private PhotoUploadService $photoUploadService;
    private string $uploadsDirectory;

    public function __construct(
        EntityManagerInterface $entityManager,
        PhotoUploadService $photoUploadService,
        string $uploadsDirectory
    ) {
        $this->entityManager = $entityManager;
        $this->photoUploadService = $photoUploadService;
        $this->uploadsDirectory = $uploadsDirectory;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $photos = $this->entityManager->getRepository(Photo::class)->findAll();
        $processed = 0;
        $errors = 0;

        $io->progressStart(count($photos));

        foreach ($photos as $photo) {
            $thumbnails = $photo->getThumbnails();
            
            // Check if photo needs thumbnails
            if (empty($thumbnails) || !is_array($thumbnails)) {
                try {
                    $originalPath = $this->uploadsDirectory . '/' . $photo->getSrc();
                    
                    if (file_exists($originalPath)) {
                        // Generate thumbnails
                        $newThumbnails = $this->photoUploadService->generateThumbnails($photo->getSrc());
                        
                        if (!empty($newThumbnails)) {
                            $photo->setThumbnails($newThumbnails);
                            $this->entityManager->flush();
                            $processed++;
                        }
                    } else {
                        $io->warning("Original file not found for photo ID {$photo->getId()}: {$photo->getSrc()}");
                        $errors++;
                    }
                } catch (\Exception $e) {
                    $io->error("Failed to generate thumbnails for photo ID {$photo->getId()}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            $io->progressAdvance();
        }

        $io->progressFinish();

        $io->success("Thumbnail generation completed!");
        $io->info("Processed: {$processed} photos");
        
        if ($errors > 0) {
            $io->warning("Errors: {$errors} photos");
        }

        return Command::SUCCESS;
    }
}

<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Role;
use App\Entity\Album;
use App\Entity\Photo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-sample-data',
    description: 'Create sample users, albums, and photos for testing admin dashboard',
)]
class CreateSampleDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('users', null, InputOption::VALUE_OPTIONAL, 'Number of sample users to create', 10)
            ->addOption('albums', null, InputOption::VALUE_OPTIONAL, 'Number of sample albums to create', 15)
            ->addOption('photos', null, InputOption::VALUE_OPTIONAL, 'Number of sample photos to create', 25)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userCount = (int) $input->getOption('users');
        $albumCount = (int) $input->getOption('albums');
        $photoCount = (int) $input->getOption('photos');

        $io->title('Creating Sample Data for QwePic Admin Dashboard');

        // Get roles
        $userRole = $this->entityManager->getRepository(Role::class)->findOneBy(['name' => 'user']);
        if (!$userRole) {
            $io->error('User role not found. Please run app:setup-admin first.');
            return Command::FAILURE;
        }

        // Create sample users
        $io->section('Creating Sample Users');
        $users = [];
        $statuses = ['active', 'suspended', 'banned'];
        
        for ($i = 1; $i <= $userCount; $i++) {
            $user = new User();
            $user->setEmail("user{$i}@example.com");
            $user->setRole($userRole);
            $user->setStatus($statuses[array_rand($statuses)]);
            $user->setCreatedAt(new \DateTimeImmutable('-' . rand(1, 365) . ' days'));
            $user->setUpdatedAt(new \DateTimeImmutable('-' . rand(1, 30) . ' days'));
            $user->setLocation($this->getRandomLocation());
            $user->setBio($this->getRandomBio());
            
            $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
            $user->setPasswordHash($hashedPassword);
            
            $this->entityManager->persist($user);
            $users[] = $user;
        }
        
        $this->entityManager->flush();
        $io->success("Created {$userCount} sample users");

        // Create sample albums
        $io->section('Creating Sample Albums');
        $albums = [];
        $albumStatuses = ['approved', 'pending', 'rejected'];
        
        for ($i = 1; $i <= $albumCount; $i++) {
            $album = new Album();
            $album->setTitle($this->getRandomAlbumTitle());
            $album->setDescription($this->getRandomAlbumDescription());
            $album->setPhotographer($users[array_rand($users)]);
            $album->setIsPublic(rand(0, 1) === 1);
            $album->setStatus($albumStatuses[array_rand($albumStatuses)]);
            $album->setCreatedAt(new \DateTimeImmutable('-' . rand(1, 180) . ' days'));
            
            $this->entityManager->persist($album);
            $albums[] = $album;
        }
        
        $this->entityManager->flush();
        $io->success("Created {$albumCount} sample albums");

        // Create sample photos
        $io->section('Creating Sample Photos');
        $photoStatuses = ['approved', 'pending', 'rejected'];
        
        for ($i = 1; $i <= $photoCount; $i++) {
            $photo = new Photo();
            $photo->setTitle($this->getRandomPhotoTitle());
            $photo->setDescription($this->getRandomPhotoDescription());
            $photo->setPhotographer($users[array_rand($users)]);
            $photo->setSrc("sample-photo-{$i}.jpg");
            $photo->setIsPublic(rand(0, 1) === 1);
            $photo->setStatus($photoStatuses[array_rand($photoStatuses)]);
            $photo->setTags($this->getRandomTags());
            $photo->setThumbnails([
                'small' => "thumb-small-{$i}.jpg",
                'medium' => "thumb-medium-{$i}.jpg",
                'large' => "thumb-large-{$i}.jpg"
            ]);
            $photo->setCreatedAt(new \DateTimeImmutable('-' . rand(1, 90) . ' days'));
            
            // Randomly assign to album
            if (rand(0, 1) === 1 && !empty($albums)) {
                $photo->setAlbum($albums[array_rand($albums)]);
            }
            
            $this->entityManager->persist($photo);
        }
        
        $this->entityManager->flush();
        $io->success("Created {$photoCount} sample photos");

        $io->section('Summary');
        $io->text([
            "Sample data created successfully!",
            "",
            "Created:",
            "- {$userCount} users with various statuses",
            "- {$albumCount} albums with different approval states",
            "- {$photoCount} photos with random metadata",
            "",
            "You can now test the admin dashboard at /admin",
            "Login with: admin@qwepic.com / admin123"
        ]);

        return Command::SUCCESS;
    }

    private function getRandomLocation(): string
    {
        $locations = [
            'New York, NY', 'Los Angeles, CA', 'Chicago, IL', 'Houston, TX',
            'Phoenix, AZ', 'Philadelphia, PA', 'San Antonio, TX', 'San Diego, CA',
            'Dallas, TX', 'San Jose, CA', 'Austin, TX', 'Jacksonville, FL'
        ];
        return $locations[array_rand($locations)];
    }

    private function getRandomBio(): string
    {
        $bios = [
            'Passionate photographer capturing life\'s beautiful moments.',
            'Street photography enthusiast exploring urban landscapes.',
            'Nature lover documenting wildlife and scenic views.',
            'Portrait photographer specializing in authentic expressions.',
            'Travel photographer sharing stories from around the world.',
            'Wedding photographer creating timeless memories.',
            'Fashion photographer with an eye for style.',
            'Documentary photographer telling important stories.'
        ];
        return $bios[array_rand($bios)];
    }

    private function getRandomAlbumTitle(): string
    {
        $titles = [
            'Urban Explorations', 'Nature\'s Beauty', 'Portrait Sessions',
            'Wedding Memories', 'Street Life', 'Landscape Adventures',
            'City Nights', 'Golden Hour', 'Black & White Collection',
            'Travel Diaries', 'Architecture Focus', 'Wildlife Encounters'
        ];
        return $titles[array_rand($titles)];
    }

    private function getRandomAlbumDescription(): string
    {
        $descriptions = [
            'A collection of my favorite shots from recent adventures.',
            'Capturing the essence of urban life through my lens.',
            'Beautiful moments frozen in time.',
            'Exploring the relationship between light and shadow.',
            'A journey through different perspectives and emotions.',
            'Documenting the world as I see it.',
            'Stories told through photography.',
            'Moments that matter, preserved forever.'
        ];
        return $descriptions[array_rand($descriptions)];
    }

    private function getRandomPhotoTitle(): string
    {
        $titles = [
            'Golden Sunset', 'City Reflections', 'Morning Dew',
            'Street Corner', 'Mountain Peak', 'Ocean Waves',
            'Urban Jungle', 'Quiet Moment', 'Vibrant Colors',
            'Shadow Play', 'Natural Light', 'Perfect Timing'
        ];
        return $titles[array_rand($titles)];
    }

    private function getRandomPhotoDescription(): string
    {
        $descriptions = [
            'Captured during a peaceful morning walk.',
            'The perfect lighting made this shot possible.',
            'A spontaneous moment that tells a story.',
            'One of my favorite compositions this year.',
            'The colors in this scene were absolutely stunning.',
            'Sometimes the best shots are unplanned.',
            'This location has always inspired me.',
            'A moment of pure serenity.'
        ];
        return $descriptions[array_rand($descriptions)];
    }

    private function getRandomTags(): array
    {
        $allTags = [
            'nature', 'urban', 'portrait', 'landscape', 'street',
            'architecture', 'travel', 'sunset', 'black-white',
            'macro', 'wildlife', 'city', 'ocean', 'mountain'
        ];
        
        $numTags = rand(2, 5);
        $selectedTags = array_rand($allTags, $numTags);
        
        if (is_array($selectedTags)) {
            return array_map(fn($index) => $allTags[$index], $selectedTags);
        } else {
            return [$allTags[$selectedTags]];
        }
    }
}

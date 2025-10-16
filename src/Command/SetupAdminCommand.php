<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:setup-admin',
    description: 'Setup admin user and roles for QwePic admin dashboard',
)]
class SetupAdminCommand extends Command
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
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Admin email address', 'admin@qwepic.com')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Admin password', 'admin123')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getOption('email');
        $password = $input->getOption('password');

        $io->title('QwePic Admin Setup');

        // Check if admin role exists, create if not
        $adminRole = $this->entityManager->getRepository(Role::class)->findOneBy(['name' => 'admin']);
        if (!$adminRole) {
            $adminRole = new Role();
            $adminRole->setName('admin');
            $this->entityManager->persist($adminRole);
            $io->success('Created admin role');
        } else {
            $io->note('Admin role already exists');
        }

        // Check if user role exists, create if not
        $userRole = $this->entityManager->getRepository(Role::class)->findOneBy(['name' => 'user']);
        if (!$userRole) {
            $userRole = new Role();
            $userRole->setName('user');
            $this->entityManager->persist($userRole);
            $io->success('Created user role');
        } else {
            $io->note('User role already exists');
        }

        // Check if admin user exists
        $adminUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$adminUser) {
            $adminUser = new User();
            $adminUser->setEmail($email);
            $adminUser->setRole($adminRole);
            $adminUser->setStatus('active');
            $adminUser->setCreatedAt(new \DateTimeImmutable());
            $adminUser->setUpdatedAt(new \DateTimeImmutable());
            
            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword($adminUser, $password);
            $adminUser->setPasswordHash($hashedPassword);
            
            $this->entityManager->persist($adminUser);
            $io->success("Created admin user: {$email}");
        } else {
            // Update existing admin user
            $adminUser->setRole($adminRole);
            $adminUser->setStatus('active');
            $hashedPassword = $this->passwordHasher->hashPassword($adminUser, $password);
            $adminUser->setPasswordHash($hashedPassword);
            $io->note("Updated existing admin user: {$email}");
        }

        $this->entityManager->flush();

        $io->section('Admin Dashboard Access');
        $io->text([
            'Admin dashboard is now ready!',
            '',
            'Access details:',
            "Email: {$email}",
            "Password: {$password}",
            '',
            'Admin dashboard URL: /admin',
            'Make sure to change the default password after first login.',
        ]);

        $io->success('Admin setup completed successfully!');

        return Command::SUCCESS;
    }
}

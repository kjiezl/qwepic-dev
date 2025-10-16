<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\LoginFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        $form = $this->createForm(LoginFormType::class);
        $form->handleRequest($request);

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'loginForm' => $form->createView(),
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $em): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if email already exists
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $form->get('email')->addError(new FormError('This email is already registered.'));
            } else {
                // Get plain password from unmapped field
                $plainPassword = $form->get('password')->getData();
                $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
                $user->setPasswordHash($hashedPassword);

                // Get role entity directly from form (EntityType)
                $role = $form->get('role')->getData();
                $user->setRole($role);

                $user->setCreatedAt(new \DateTimeImmutable());
                $user->setUpdatedAt(new \DateTimeImmutable());

                $em->persist($user);
                $em->flush();

                // $this->addFlash('success', 'Registration successful! Please log in.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): Response
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
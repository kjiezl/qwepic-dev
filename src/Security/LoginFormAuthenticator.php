<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    public const LOGIN_ROUTE = 'app_login';

    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function authenticate(Request $request): Passport
    {
        $formData = $request->request->all('login_form');

        $email = (string) ($formData['_username'] ?? '');
        $password = (string) ($formData['password'] ?? '');
        $csrfToken = (string) ($formData['_token'] ?? '');

        if ($request->hasSession()) {
            $request->getSession()->set('_security.last_username', $email);
        }

        $rememberMeBadge = new RememberMeBadge();
        if (!empty($formData['remember_me'])) {
            $rememberMeBadge->enable();
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                $rememberMeBadge,
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        /** @var \App\Entity\User $user */
        $user = $token->getUser();

        // If Photographer, redirect to home page
        if ($user->getRole() && $user->getRole()->getName() === 'Photographer') {
            return new RedirectResponse($this->router->generate('app_home'));
        }

        // Otherwise, redirect to homepage (change as needed)
        return new RedirectResponse($this->router->generate('app_home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate(self::LOGIN_ROUTE);
    }
}
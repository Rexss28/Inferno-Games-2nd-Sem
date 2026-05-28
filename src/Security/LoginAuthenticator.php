<?php

namespace App\Security;

use App\Repository\UserRepository;
// REMOVE: use App\Service\ActivityLogger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository
        // REMOVE: private ActivityLogger $activityLogger
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        // Use 'username' instead of 'email'
        $username = $request->getPayload()->getString('username');
        $password = $request->getPayload()->getString('password');

        // Debug: Log the login attempt
        error_log('=== LOGIN ATTEMPT ===');
        error_log('Username submitted: ' . $username);
        error_log('Password submitted: ' . (strlen($password) > 0 ? '******' : 'empty'));

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $username);

        return new Passport(
            new UserBadge($username, function($userIdentifier) {
                error_log('Looking for user: ' . $userIdentifier);
                
                // Load user from database by username
                $user = $this->userRepository->findOneBy(['username' => $userIdentifier]);
                
                if (!$user) {
                    error_log('User NOT found in database');
                    throw new UserNotFoundException('Invalid credentials.');
                }
                
                error_log('User FOUND: ID=' . $user->getId() . ', Username=' . $user->getUsername());
                error_log('User email: ' . $user->getEmail());
                error_log('User isVerified: ' . ($user->isVerified() ? 'true' : 'false'));
                error_log('User roles: ' . implode(', ', $user->getRoles()));
                
                // Check if user can login (active status)
                if (!$user->canLogin()) {
                    error_log('User cannot login - account deactivated');
                    throw new CustomUserMessageAuthenticationException(
                        'This account has been deactivated. Please contact an administrator.'
                    );
                }
                
                // Email verification check (regular users only, not admin/staff)
                $userRoles = $user->getRoles();
                $isAdminOrStaff = in_array('ROLE_ADMIN', $userRoles, true) || in_array('ROLE_STAFF', $userRoles, true);
                
                error_log('Is Admin or Staff: ' . ($isAdminOrStaff ? 'true' : 'false'));
                
                if (!$isAdminOrStaff && !$user->isVerified()) {
                    error_log('Blocking login - user not verified and not admin/staff');
                    throw new CustomUserMessageAuthenticationException(
                        'Your email address is not verified. Please check your inbox to verify your account.'
                    );
                }
                
                error_log('Login checks passed!');
                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        error_log('=== LOGIN SUCCESS ===');
        error_log('User logged in successfully');
        
        // ✅ REMOVE THIS - LOGIN LOGGING IS HANDLED BY JWTAuthenticationSuccessHandler FOR MOBILE
        // AND NOT NEEDED FOR WEB LOGINS (or handle separately if needed)
        // $user = $token->getUser();
        // if ($user) {
        //     $this->activityLogger->log('LOGIN', $user);
        // }
        
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            error_log('Redirecting to target path: ' . $targetPath);
            return new RedirectResponse($targetPath);
        }

        // determine roles (support TokenInterface::getRoleNames() and legacy getUser()->getRoles())
        $roles = [];
        if (method_exists($token, 'getRoleNames')) {
            $roles = $token->getRoleNames();
        } elseif ($token->getUser() && method_exists($token->getUser(), 'getRoles')) {
            $roles = $token->getUser()->getRoles();
        }

        error_log('User roles after login: ' . implode(', ', $roles));

        // priority: admin first, then staff
        if (in_array('ROLE_ADMIN', $roles, true)) {
            error_log('Redirecting to admin dashboard');
            return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
        }

        if (in_array('ROLE_STAFF', $roles, true)) {
            error_log('Redirecting to staff analytics');
            return new RedirectResponse($this->urlGenerator->generate('app_admin_analytics_index'));
        }

        // Regular users (without admin/staff roles) go to home page
        error_log('Redirecting to home page');
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        error_log('=== LOGIN FAILURE ===');
        error_log('Failure reason: ' . $exception->getMessage());
        error_log('Failure class: ' . get_class($exception));
        
        return parent::onAuthenticationFailure($request, $exception);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
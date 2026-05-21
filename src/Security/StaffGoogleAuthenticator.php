<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Psr\Log\LoggerInterface;

class StaffGoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    // Add your staff email domains here
    private const STAFF_DOMAINS = [
        'gmail.com',  // FOR TESTING ONLY - Remove in production!
    ];
    

    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private RouterInterface $router,
        private LoggerInterface $logger
    ) {}

    public function supports(Request $request): ?bool
    {
        $isSupported = $request->getPathInfo() === '/staff/google/callback';
        $this->logger->info('StaffGoogleAuthenticator supports?', [
            'path' => $request->getPathInfo(),
            'supported' => $isSupported
        ]);
        return $isSupported;
    }

    public function authenticate(Request $request): Passport
    {
        $this->logger->info('StaffGoogleAuthenticator authenticate started');
        
        $client = $this->clientRegistry->getClient('google_staff');
        $accessToken = $this->fetchAccessToken($client);
        
        $this->logger->info('Access token obtained');
        
        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                
                $email = $googleUser->getEmail();
                $googleId = $googleUser->getId();
                $googleName = $googleUser->getName();
                
                $this->logger->info('Google user data', [
                    'email' => $email,
                    'googleId' => $googleId,
                    'name' => $googleName
                ]);
                
                // Verify this is a staff email
                if (!$this->isStaffEmail($email)) {
                    $this->logger->warning('Non-staff email attempted', ['email' => $email]);
                    throw new AuthenticationException('Access denied. Staff email required.');
                }
                
                $this->logger->info('Staff email verified', ['email' => $email]);
                
                // Check if user exists by Google ID
                $existingUser = $this->userRepository->findOneBy(['googleId' => $googleId]);
                if ($existingUser) {
                    $this->logger->info('User found by Google ID', ['user_id' => $existingUser->getId()]);
                    $this->ensureStaffRole($existingUser);
                    return $existingUser;
                }
                
                // Check if user exists by email
                $userByEmail = $this->userRepository->findOneBy(['email' => $email]);
                if ($userByEmail) {
                    $this->logger->info('User found by email', ['user_id' => $userByEmail->getId()]);
                    $userByEmail->setGoogleId($googleId);
                    $this->ensureStaffRole($userByEmail);
                    $this->entityManager->flush();
                    return $userByEmail;
                }
                
                // Create new staff user
                $this->logger->info('Creating new staff user', ['email' => $email]);
                $user = new User();
                $user->setEmail($email);
                $user->setUsername($googleName ?? explode('@', $email)[0]);
                $user->setGoogleId($googleId);
                $user->setIsVerified(true);
                $user->setStatus(User::STATUS_ACTIVE);
                $user->setRoles(['ROLE_STAFF']);
                
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                
                $this->logger->info('New staff user created', ['user_id' => $user->getId()]);
                
                return $user;
            })
        );
    }

    private function ensureStaffRole(User $user): void
    {
        $roles = $user->getRoles();
        $this->logger->info('Current roles', ['roles' => $roles]);
        
        if (!in_array('ROLE_STAFF', $roles)) {
            $roles[] = 'ROLE_STAFF';
            $user->setRoles($roles);
            $this->entityManager->flush();
            $this->logger->info('Added ROLE_STAFF to user', ['new_roles' => $roles]);
        } else {
            $this->logger->info('User already has ROLE_STAFF');
        }
    }

    private function isStaffEmail(string $email): bool
    {
        $domain = substr(strrchr($email, "@"), 1);
        $isStaff = in_array($domain, self::STAFF_DOMAINS);
        $this->logger->info('Staff email check', [
            'email' => $email,
            'domain' => $domain,
            'is_staff' => $isStaff
        ]);
        return $isStaff;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        $roles = $user->getRoles();
        
        $this->logger->info('Authentication success', [
            'user_email' => $user->getEmail(),
            'roles' => $roles,
            'firewall' => $firewallName
        ]);
        
        // Redirect based on role (same as your LoginAuthenticator)
        if (in_array('ROLE_ADMIN', $roles, true)) {
            $this->logger->info('Redirecting to admin dashboard');
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }
        
        if (in_array('ROLE_STAFF', $roles, true)) {
            $this->logger->info('Redirecting to staff analytics');
            return new RedirectResponse($this->router->generate('app_admin_analytics_index'));
        }
        
        $this->logger->info('Redirecting to home page');
        return new RedirectResponse($this->router->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->error('Authentication failure', [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        $request->getSession()->set('oauth_error', $exception->getMessage());
        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $this->logger->info('Authentication start - redirecting to login');
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
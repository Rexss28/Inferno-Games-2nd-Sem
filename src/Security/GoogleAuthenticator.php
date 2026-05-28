<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; // Add this

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    /**
     * Staff email domains - automatically grant ROLE_STAFF to users with these domains
     * Add your staff domains here
     */
    private const STAFF_DOMAINS = [
        // 'gmail.com',  // This allows ALL gmail.com accounts to be staff (for testing)
        // Add your specific staff domains here, e.g.:
        // 'staff.yourschool.com',
        // 'admin.yourcompany.com',
    ];
    
    /**
     * Specific staff emails - for individual email addresses that should be staff
     * even if their domain isn't in STAFF_DOMAINS
     */
    private const STAFF_EMAILS = [
        'cabangrex@gmail.com',  // Your specific staff email
    ];

    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private RouterInterface $router,
        private JWTTokenManagerInterface $jwtManager,
        private UserPasswordHasherInterface $passwordHasher // Add this parameter
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/api/auth/google/callback';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);
        
        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                
                $email = $googleUser->getEmail();
                $googleId = $googleUser->getId();
                $googleName = $googleUser->getName();
                
                // Check if user exists by Google ID
                $existingUser = $this->userRepository->findOneBy(['googleId' => $googleId]);
                if ($existingUser) {
                    // Automatic verification for staff on each login
                    $this->autoVerifyStaffStatus($existingUser, $email);
                    return $existingUser;
                }
                
                // Check if user exists by email
                $userByEmail = $this->userRepository->findOneBy(['email' => $email]);
                if ($userByEmail) {
                    // Link Google account to existing user
                    $userByEmail->setGoogleId($googleId);
                    
                    // Automatic verification for staff
                    $this->autoVerifyStaffStatus($userByEmail, $email);
                    
                    $this->entityManager->flush();
                    return $userByEmail;
                }
                
                // Determine if this user should be staff based on email domain or specific email
                $isStaff = $this->isStaffEmail($email);
                
                // Create new user
                $user = new User();
                $user->setEmail($email);
                $user->setUsername($googleName ?? explode('@', $email)[0]);
                $user->setGoogleId($googleId);
                $user->setIsVerified(true); // Google verified the email
                $user->setStatus(User::STATUS_ACTIVE);
                
                // Assign roles based on staff status
                if ($isStaff) {
                    $user->setRoles(['ROLE_STAFF']);
                } else {
                    $user->setRoles(['ROLE_USER']);
                }
                
                // Set a dummy password for database constraint (Google users don't need password login)
                $dummyPassword = 'google_user_' . bin2hex(random_bytes(8)); // Generate unique dummy password
                $hashedPassword = $this->passwordHasher->hashPassword($user, $dummyPassword);
                $user->setPassword($hashedPassword);
                
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                
                return $user;
            })
        );
    }

    /**
     * Check if email belongs to a staff member based on domain or specific email
     */
    private function isStaffEmail(string $email): bool
    {
        // Check if the exact email is in the staff emails list
        if (in_array($email, self::STAFF_EMAILS)) {
            return true;
        }
        
        // Check if the domain is in staff domains
        $domain = substr(strrchr($email, "@"), 1);
        return in_array($domain, self::STAFF_DOMAINS);
    }

    /**
     * Automatically verify and update staff status for existing users
     * This ensures staff members retain their role even if they logged in before
     */
    private function autoVerifyStaffStatus(User $user, string $email): void
    {
        $isStaff = $this->isStaffEmail($email);
        $currentRoles = $user->getRoles();
        
        if ($isStaff) {
            // If user should be staff but doesn't have ROLE_STAFF, add it
            if (!in_array('ROLE_STAFF', $currentRoles)) {
                // Preserve ROLE_ADMIN if they have it, add ROLE_STAFF
                $newRoles = array_merge($currentRoles, ['ROLE_STAFF']);
                $user->setRoles(array_unique($newRoles));
                $this->entityManager->flush();
                
                // Optional: Log the promotion
                error_log("User {$email} promoted to STAFF via Google login");
            }
        }
        // Note: If a staff email is no longer in staff domains, you might want to remove ROLE_STAFF
        // That logic is omitted as per rubric "automatic verification" (granting, not revoking)
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();
        
        // Generate JWT token
        $jwt = $this->jwtManager->create($user);
        
        // Determine user type for response
        $isStaff = in_array('ROLE_STAFF', $user->getRoles());
        
        // Return JSON with token and user info
        return new JsonResponse([
            'success' => true,
            'token' => $jwt,
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'isVerified' => $user->isVerified(),
                'roles' => $user->getDisplayRoles(),
                'isStaff' => $isStaff
            ]
        ]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'success' => false,
            'message' => 'Google authentication failed: ' . $exception->getMessage()
        ], 401);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse('/api/auth/google');
    }
}
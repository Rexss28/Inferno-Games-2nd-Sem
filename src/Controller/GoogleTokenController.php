<?php
// app/Controller/GoogleTokenController.php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GoogleTokenController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private JWTTokenManagerInterface $jwtManager
    ) {}

    #[Route('/api/auth/google/token', name: 'app_google_token_auth', methods: ['POST'])]
    public function authenticateWithToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $idToken = $data['token'] ?? null;
        
        if (!$idToken) {
            return new JsonResponse(['error' => 'No token provided'], 400);
        }
        
        try {
            // Verify the Google ID token using Google's tokeninfo endpoint
            // This doesn't require the google/apiclient library
            $verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $idToken;
            
            // Use stream context for better error handling
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents($verifyUrl, false, $context);
            
            if ($response === false) {
                return new JsonResponse(['error' => 'Failed to verify Google token'], 401);
            }
            
            $payload = json_decode($response, true);
            
            // Check if token is valid
            if (!isset($payload['email'])) {
                return new JsonResponse(['error' => 'Invalid Google token: ' . ($payload['error'] ?? 'unknown error')], 401);
            }
            
            // Optional: Verify the audience (client ID) matches your app
            $expectedClientId = $_ENV['GOOGLE_CLIENT_ID'] ?? null;
            if ($expectedClientId && isset($payload['aud']) && $payload['aud'] !== $expectedClientId) {
                return new JsonResponse(['error' => 'Token audience mismatch'], 401);
            }
            
            $email = $payload['email'];
            $googleId = $payload['sub'];
            $googleName = $payload['name'] ?? explode('@', $email)[0];
            
            // Find or create user
            $user = $this->userRepository->findOneBy(['googleId' => $googleId]);
            if (!$user) {
                $user = $this->userRepository->findOneBy(['email' => $email]);
                if ($user) {
                    $user->setGoogleId($googleId);
                } else {
                    // Create new user with ROLE_USER (not staff)
                    $user = new User();
                    $user->setEmail($email);
                    $user->setUsername($googleName);
                    $user->setGoogleId($googleId);
                    $user->setIsVerified(true);
                    $user->setStatus(User::STATUS_ACTIVE);
                    // No need to set roles - getRoles() automatically adds ROLE_USER
                    $this->entityManager->persist($user);
                }
            }
            
            $this->entityManager->flush();
            
            // Generate JWT token
            $jwt = $this->jwtManager->create($user);
            
            // Get display roles (excludes ROLE_USER)
            $displayRoles = $user->getDisplayRoles();
            
            return new JsonResponse([
                'success' => true,
                'token' => $jwt,
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'roles' => $displayRoles,
                    'isVerified' => $user->isVerified(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Authentication failed: ' . $e->getMessage()], 500);
        }
    }
}
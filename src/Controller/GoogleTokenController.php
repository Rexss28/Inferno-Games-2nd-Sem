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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class GoogleTokenController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private JWTTokenManagerInterface $jwtManager,
        private UserPasswordHasherInterface $passwordHasher  // Add this
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
            // Verify the Google ID token
            $verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $idToken;
            
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
            
            if (!isset($payload['email'])) {
                return new JsonResponse(['error' => 'Invalid Google token'], 401);
            }
            
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
                    // Create new user with dummy password for Google Sign-In users
                    $dummyPassword = 'rex123';  // Dummy password for Google users
                    $hashedPassword = $this->passwordHasher->hashPassword(new User(), $dummyPassword);
                    
                    $user = new User();
                    $user->setEmail($email);
                    $user->setUsername($googleName);
                    $user->setGoogleId($googleId);
                    $user->setIsVerified(true);
                    $user->setStatus(User::STATUS_ACTIVE);
                    $user->setPassword($hashedPassword);  // Set the dummy hashed password
                    
                    $this->entityManager->persist($user);
                }
            }
            
            $this->entityManager->flush();
            
            // Generate JWT token
            $jwt = $this->jwtManager->create($user);
            
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
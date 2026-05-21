<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
class ApiEmailVerificationController extends AbstractController
{
    public function __construct(
        private EmailVerificationService $emailVerificationService,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Verify email with token
     * POST /api/verify-email
     */
    #[Route('/verify-email', name: 'api_verify_email', methods: ['POST'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['token'])) {
            return $this->json([
                'success' => false,
                'message' => 'Verification token is required'
            ], 400);
        }
        
        $user = $this->emailVerificationService->verifyToken($data['token']);
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid or expired verification token'
            ], 400);
        }
        
        return $this->json([
            'success' => true,
            'message' => 'Email verified successfully',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUserIdentifier(),
                'email' => $user->getEmail(),
                'isVerified' => $user->isVerified()
            ]
        ], 200);
    }

    /**
     * Resend verification email
     * POST /api/resend-verification
     */
    #[Route('/resend-verification', name: 'api_resend_verification', methods: ['POST'])]
    public function resendVerification(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        if ($user->isVerified()) {
            return $this->json([
                'success' => false,
                'message' => 'Email is already verified'
            ], 400);
        }

        // Generate new token
        $verificationToken = $this->emailVerificationService->generateVerificationToken();
        $user->setVerificationToken($verificationToken);
        $this->entityManager->flush();

        // Create verification URL
        $verificationUrl = $this->generateUrl(
            'app_verify_email',
            ['token' => $verificationToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Send email
        try {
            $this->emailVerificationService->sendVerificationEmail($user, $verificationUrl);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to send verification email'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Verification email sent successfully'
        ], 200);
    }

    /**
     * Check verification status
     * GET /api/verification-status
     */
    #[Route('/verification-status', name: 'api_verification_status', methods: ['GET'])]
    public function verificationStatus(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'isVerified' => $user->isVerified(),
                'email' => $user->getEmail()
            ]
        ], 200);
    }
}
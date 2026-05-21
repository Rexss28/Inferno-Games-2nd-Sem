<?php

namespace App\Controller\Customer;

use App\Entity\User;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/customer')]
#[IsGranted('ROLE_USER')]
class CustomerProfileController extends AbstractController
{
    #[Route('/profile', name: 'api_customer_profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        return $this->json([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getDisplayRoles(),
            'status' => $user->getStatus(),
            'isVerified' => $user->isVerified(),
        ]);
    }

    #[Route('/profile', name: 'api_customer_profile_update', methods: ['PUT'])]
    public function updateProfile(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        
        if (isset($data['username'])) {
            $user->setUsername($data['username']);
        }

        $em->flush();

        return $this->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
            ]
        ]);
    }

    // ✅ Logout endpoint - logs user logout activity
    #[Route('/logout', name: 'api_customer_logout', methods: ['POST'])]
    public function logout(ActivityLogger $activityLogger): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Log the logout activity to admin dashboard
        $activityLogger->log('LOGOUT', $user);
        
        return $this->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }
}
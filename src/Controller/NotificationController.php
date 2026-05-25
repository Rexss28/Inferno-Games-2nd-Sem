<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    #[Route('/token', name: 'api_save_fcm_token', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function saveFcmToken(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        
        $fcmToken = $data['token'] ?? null;
        
        if (!$fcmToken) {
            return $this->json(['error' => 'Token is required'], 400);
        }
        
        $user->setFcmToken($fcmToken);
        $em->flush();
        
        return $this->json(['message' => 'Token saved successfully']);
    }
}
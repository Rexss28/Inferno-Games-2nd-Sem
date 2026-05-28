<?php

namespace App\Security;

use App\Entity\User;
use App\Service\ActivityLogger;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class JWTAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private static $loggedUsers = []; // Track already logged users for this request

    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private ActivityLogger $activityLogger
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        /** @var User $user */
        $user = $token->getUser();
        
        // ✅ LOG MOBILE LOGIN ACTIVITY - CHECK FOR DUPLICATE
        $userId = $user->getId();
        $requestId = spl_object_hash($request); // Unique request identifier
        
        // Only log if not already logged in this request
        if (!isset(self::$loggedUsers[$userId][$requestId])) {
            self::$loggedUsers[$userId][$requestId] = true;
            $this->activityLogger->log('LOGIN', $user);
        }
        
        // ✅ CHECK FOR ADMIN/STAFF ROLES - BLOCK FROM MOBILE APP
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_STAFF', $roles, true)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Access denied. Admin/Staff accounts cannot login from mobile app. Please use the web portal.'
            ], 403);
        }
        
        // Check if email is verified (for regular users)
        if (!$user->isVerified()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Please verify your email address before logging in',
                'verified' => false
            ], 403);
        }
        
        // Generate JWT token
        $jwt = $this->jwtManager->create($user);
        
        return new JsonResponse([
            'success' => true,
            'token' => $jwt,
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUserIdentifier(),
                'email' => $user->getEmail(),
                'roles' => $user->getDisplayRoles(),
                'isVerified' => $user->isVerified()
            ]
        ]);
    }
}
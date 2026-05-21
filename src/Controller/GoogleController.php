<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoogleController extends AbstractController
{
    #[Route('/api/auth/google', name: 'app_google_auth')]
    public function connect(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('google')->redirect([
            'email',
            'profile'
        ]);
    }

    #[Route('/api/auth/google/callback', name: 'app_google_auth_callback')]
    public function callback(): Response
    {
        // The GoogleAuthenticator handles this route
        return new Response('', 200);
    }
}
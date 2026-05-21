<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StaffGoogleController extends AbstractController
{
    #[Route('/staff/google', name: 'app_staff_google_auth')]
    public function connect(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('google_staff')->redirect([
            'email',
            'profile'
        ]);
    }

    #[Route('/staff/google/callback', name: 'app_staff_google_callback')]
    public function callback(): Response
    {
        // The StaffGoogleAuthenticator handles this route
        return new Response('', 200);
    }
}
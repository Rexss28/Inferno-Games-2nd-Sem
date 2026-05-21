<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AboutPageController extends AbstractController
{
    #[Route('/about/page', name: 'app_about_page')]
    public function index(): Response
    {
        return $this->render('about_page/index.html.twig', [
            'team_member_name' => 'Rex John Cabanag',
            'team_positions' => [
                'Founder & Project Lead',
                'Full-Stack Developer',
                'UI / UX & Brand Design',
                'Frontend Engineering (Twig, CSS, JavaScript)',
                'Database & Infrastructure',
                'Quality Assurance & Release',
                'Content Strategy & Store Copy',
            ],
        ]);
    }
}

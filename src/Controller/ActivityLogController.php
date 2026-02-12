<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/activity-logs')]  // Changed from '/admin/activity-logs'
#[IsGranted('ROLE_ADMIN')]
class ActivityLogController extends AbstractController
{
    #[Route('/', name: 'activity_log_index', methods: ['GET'])]  // Changed from 'admin_activity_logs_index'
    public function index(Request $request, ActivityLogRepository $logRepository): Response
    {
        $filters = [
            'user' => $request->query->get('user'),
            'action' => $request->query->get('action'),
            'date' => $request->query->get('date'),
        ];

        $logs = $logRepository->findByFilters($filters);

        return $this->render('admin/activity_log/index.html.twig', [
            'logs' => $logs,
            'filters' => $filters,
        ]);
    }

    #[Route('/{id}', name: 'activity_log_show', methods: ['GET'])]  // Changed from 'admin_activity_logs_show'
    public function show(ActivityLog $log): Response
    {
        return $this->render('admin/activity_log/show.html.twig', [
            'log' => $log,
        ]);
    }
}
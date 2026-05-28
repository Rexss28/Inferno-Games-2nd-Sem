<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/activity-logs')]
#[IsGranted('ROLE_ADMIN')]
class ActivityLogController extends AbstractController
{
    #[Route('/', name: 'activity_log_index', methods: ['GET'])]
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

    #[Route('/{id}', name: 'activity_log_show', methods: ['GET'])]
    public function show(ActivityLog $log): Response
    {
        return $this->render('admin/activity_log/show.html.twig', [
            'log' => $log,
        ]);
    }

    // ✅ API endpoint for fetching latest logs (for real-time updates)
    #[Route('/api/latest', name: 'activity_log_api_latest', methods: ['GET'])]
    public function getLatestLogs(Request $request, ActivityLogRepository $logRepository): Response
    {
        $afterId = $request->query->get('afterId');
        $limit = $request->query->get('limit', 10);
        
        $filters = [
            'user' => $request->query->get('user'),
            'action' => $request->query->get('action'),
            'date' => $request->query->get('date'),
        ];

        $logs = $logRepository->findByFilters($filters, $limit);
        
        // If afterId is provided, only return logs with ID > afterId
        if ($afterId !== null && $afterId !== '') {
            $afterIdInt = (int) $afterId;
            $logs = array_filter($logs, function($log) use ($afterIdInt) {
                return $log->getId() > $afterIdInt;
            });
        }
        
        $logData = [];
        foreach ($logs as $log) {
            $logData[] = [
                'id' => $log->getId(),
                'userId' => $log->getUserId(),
                'username' => $log->getUsername(),
                'role' => $log->getRole(),
                'action' => $log->getAction(),
                'targetData' => $log->getTargetData(),
                'createdAt' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }
        
        return $this->json($logData);
    }
}
<?php

namespace App\Controller;

use App\Repository\GameManagementRepository;
use App\Repository\OrderRepository;
use App\Repository\StockRepository;
use App\Repository\UserRepository;
use App\Repository\ActivityLogRepository; // ADD THIS
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/dashboard')]
final class AdminDashboardController extends AbstractController
{
    #[Route(name: 'app_admin_dashboard', methods: ['GET'])]
    public function index(
        GameManagementRepository $gameRepo,
        OrderRepository $orderRepo,
        StockRepository $stockRepo,
        UserRepository $userRepo,
        ActivityLogRepository $activityLogRepo // ADD THIS
    ): Response {
        // 1️⃣ Quick stats
        $totalGames = count($gameRepo->findAll());

        // Replace total orders and pending orders with total users and total staff
        $users = $userRepo->findAll();

        $totalUsers = 0;
        $totalStaff = 0;

        foreach ($users as $user) {
            $roles = method_exists($user, 'getRoles') ? $user->getRoles() : [];

            // Count staff explicitly when ROLE_STAFF is present
            if (in_array('ROLE_STAFF', $roles, true)) {
                $totalStaff++;
            }

            // Treat "customers" as accounts that are NOT staff and NOT admin.
            // This prevents admin/staff from being counted as users even if getRoles() returns ROLE_USER by default.
            if (!in_array('ROLE_STAFF', $roles, true) && !in_array('ROLE_ADMIN', $roles, true)) {
                $totalUsers++;
            }
        }

        // Total revenue
        $totalRevenue = 0;
        foreach ($orderRepo->findAll() as $order) {
            $totalRevenue += (float) $order->getTotalAmount();
        }

        // Low stock alert
        $lowStockGames = $stockRepo->createQueryBuilder('s')
            ->where('s.availableQuantity < 10')
            ->getQuery()
            ->getResult();

        // Recent activity logs (top 5) - ADD THIS
        $recentActivityLogs = $activityLogRepo->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Recent games
        $recentGames = $gameRepo->createQueryBuilder('g')
            ->orderBy('g.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('Admin/admin_dashboard/index.html.twig', [
            'totalGames' => $totalGames,
            'totalUsers' => $totalUsers,
            'totalStaff' => $totalStaff,
            'totalRevenue' => $totalRevenue,
            'lowStockGames' => $lowStockGames,
            'recentActivityLogs' => $recentActivityLogs, // CHANGED: from recentOrders
            'recentGames' => $recentGames,
        ]);
    }
}
<?php

namespace App\Controller;

use App\Repository\GameManagementRepository;
use App\Repository\OrderRepository;
use App\Repository\StockRepository;
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
        StockRepository $stockRepo
    ): Response {
        // 1️⃣ Quick stats
        $totalGames = count($gameRepo->findAll());
        $totalOrders = count($orderRepo->findAll());

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

        // Pending orders
        $pendingOrders = $orderRepo->findBy(['status' => 'Pending']);
        $pendingCount = count($pendingOrders);

        // Recent orders
        $recentOrders = $orderRepo->createQueryBuilder('o')
            ->orderBy('o.id', 'DESC')
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
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'pendingCount' => $pendingCount,
            'lowStockGames' => $lowStockGames,
            'recentOrders' => $recentOrders,
            'recentGames' => $recentGames,
        ]);
    }
}

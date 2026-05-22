<?php

namespace App\Controller;

use App\Repository\GameManagementRepository;
use App\Repository\OrderRepository;
use App\Repository\StockRepository;
use App\Repository\LicenseKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/analytics')]
final class AnalyticController extends AbstractController
{
    #[Route(name: 'app_admin_analytics_index', methods: ['GET'])]
    public function index(
        GameManagementRepository $gameRepo,
        OrderRepository $orderRepo,
        StockRepository $stockRepo,
        LicenseKeyRepository $licenseRepo,
        EntityManagerInterface $em
    ): Response {
        // 1️⃣ Basic Totals
        $totalGames = $gameRepo->count([]);
        $totalOrders = $orderRepo->count([]);
        $totalStocks = $stockRepo->count([]);
        
        // ✅ FIXED: Only count AVAILABLE license keys
        $availableLicenseKeys = $licenseRepo->count(['status' => 'Available']);
        $soldLicenseKeys = $licenseRepo->count(['status' => 'Sold']);
        $totalLicenseKeys = $availableLicenseKeys + $soldLicenseKeys;

        // 2️⃣ Total Revenue (completed orders only)
        $totalRevenue = (float) $em->createQueryBuilder()
            ->select('COALESCE(SUM(o.totalAmount), 0)')
            ->from('App\Entity\Order', 'o')
            ->where('o.status = :status')
            ->setParameter('status', 'Completed')
            ->getQuery()
            ->getSingleScalarResult();

        // 3️⃣ Low Stock Alerts
        $lowStockGames = $stockRepo->createQueryBuilder('s')
            ->leftJoin('s.game', 'g')
            ->where('s.availableQuantity < 15')
            ->orderBy('s.availableQuantity', 'ASC')
            ->getQuery()
            ->getResult();

        $lowStockData = array_map(function ($stock) {
            return [
                'id' => $stock->getId(),
                'game' => $stock->getGame() ? $stock->getGame()->getTitle() : 'Unknown',
                'availableQuantity' => $stock->getAvailableQuantity(),
                'totalQuantity' => $stock->getTotalQuantity(),
                'status' => $stock->getAvailableQuantity() < 10 ? 'Critical' : 'Low',
            ];
        }, $lowStockGames);

        // 4️⃣ Orders by Status
        $statuses = ['completed', 'pending', 'processing', 'cancelled'];
        $ordersByStatus = [];
        foreach ($statuses as $status) {
            $count = $orderRepo->count(['status' => ucfirst($status)]);
            $ordersByStatus[] = [
                'status' => $status,
                'count' => $count,
                'percentage' => $totalOrders > 0 ? round(($count / $totalOrders) * 100, 1) : 0,
            ];
        }

        // 5️⃣ Top-Selling Games (by order count)
        $topSellingGames = $em->createQueryBuilder()
            ->select('g.title AS title, COUNT(o.id) AS orders')
            ->from('App\Entity\Order', 'o')
            ->join('o.game', 'g')
            ->groupBy('g.id')
            ->orderBy('orders', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // 6️⃣ Top Performing Games (by total revenue)
        $topPerformingGames = $em->createQueryBuilder()
            ->select('g.title AS title, COUNT(o.id) AS orderCount, SUM(o.totalAmount) AS totalRevenue')
            ->from('App\Entity\Order', 'o')
            ->join('o.game', 'g')
            ->where('o.status = :status')
            ->setParameter('status', 'Completed')
            ->groupBy('g.id')
            ->orderBy('totalRevenue', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $topPerformingData = array_map(function ($game) {
            return [
                'title' => $game['title'],
                'orderCount' => $game['orderCount'],
                'totalRevenue' => (float) $game['totalRevenue'],
            ];
        }, $topPerformingGames);

        return $this->render('Admin/analytic/index.html.twig', [
            'totalGames' => $totalGames,
            'totalOrders' => $totalOrders,
            'totalStocks' => $totalStocks,
            'totalLicenseKeys' => $totalLicenseKeys,
            'availableLicenseKeys' => $availableLicenseKeys,
            'soldLicenseKeys' => $soldLicenseKeys,
            'totalRevenue' => $totalRevenue,
            'ordersByStatus' => $ordersByStatus,
            'lowStockGames' => $lowStockData,
            'topSellingGames' => json_encode($topSellingGames),
            'topPerformingGames' => $topPerformingData,
        ]);
    }
}
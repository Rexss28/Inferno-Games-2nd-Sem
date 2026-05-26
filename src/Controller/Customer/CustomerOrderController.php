<?php

namespace App\Controller\Customer;

use App\Entity\Order;
use App\Entity\GameManagement;
use App\Entity\LicenseKey;
use App\Entity\User;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders')]
#[IsGranted('ROLE_USER')]
class CustomerOrderController extends AbstractController
{
    #[Route('', name: 'api_orders_create', methods: ['POST'])]
    public function createOrder(
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $em,
        ActivityLogger $activityLogger
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['items']) || empty($data['items'])) {
            return $this->json(['error' => 'Cart is empty'], 400);
        }

        $createdOrders = [];
        $alreadyPurchased = [];
        $totalAmount = 0;
        $purchasedGames = [];

        foreach ($data['items'] as $item) {
            $game = $em->getRepository(GameManagement::class)->find($item['gameId']);
            if (!$game) {
                return $this->json(['error' => "Game {$item['gameId']} not found"], 404);
            }
            
            // ✅ CHECK STOCK AVAILABILITY
            $stock = $game->getStock();
            if (!$stock || $stock->getAvailableQuantity() < $item['quantity']) {
                return $this->json(['error' => "Not enough stock for {$game->getTitle()}"], 400);
            }
            
            // Check if user already purchased this game
            $existingOrder = $em->getRepository(Order::class)->findOneBy([
                'customer' => $user,
                'game' => $game,
                'status' => 'Completed'
            ]);
            
            if ($existingOrder) {
                $alreadyPurchased[] = $game->getTitle();
                continue;
            }
            
            // Find available license key
            $licenseKey = $em->getRepository(LicenseKey::class)->findOneBy([
                'game' => $game,
                'status' => 'Available'
            ]);
            
            if (!$licenseKey) {
                return $this->json(['error' => "No license keys available for {$game->getTitle()}"], 400);
            }
            
            // ✅ DECREASE STOCK
            $newQuantity = $stock->getAvailableQuantity() - $item['quantity'];
            $stock->setAvailableQuantity($newQuantity);
            $stock->updateStatusAutomatically();
            $em->persist($stock);
            
            // Create a separate order for each game
            $order = new Order();
            $order->setOrderNumber('INF-' . time() . '-' . $user->getId() . '-' . $game->getId());
            $order->setQuantity($item['quantity']);
            $order->setTotalAmount((string) ($game->getPrice() * $item['quantity']));
            $order->setStatus('Completed');
            $order->setCustomer($user);
            $order->setGame($game);
            
            $em->persist($order);
            
            // Assign license key to order
            $licenseKey->setOrder($order);
            $licenseKey->setStatus('Sold');
            $em->persist($licenseKey);
            
            // ✅ SEND PUSH NOTIFICATION TO USER (using FCM V1 API)
            if ($user->getFcmToken()) {
                $this->sendPushNotificationV1(
                    $user->getFcmToken(),
                    'Order Confirmed! 🎮',
                    "Your order #{$order->getOrderNumber()} for {$game->getTitle()} has been placed successfully.",
                    ['order_id' => (string) $order->getId(), 'type' => 'order_confirmation']
                );
            }
            
            $createdOrders[] = $order;
            $totalAmount += (float) $game->getPrice() * $item['quantity'];
            $purchasedGames[] = $game->getTitle() . ' (x' . $item['quantity'] . ')';
        }

        // If user tried to purchase already-owned games only
        if (!empty($alreadyPurchased) && empty($createdOrders)) {
            return $this->json([
                'error' => 'You already own these games: ' . implode(', ', $alreadyPurchased)
            ], 400);
        }

        // Flush all orders to database
        $em->flush();
        
        // Clear cart after order
        $session->set('user_cart_' . $user->getId(), []);

        // Log the checkout/purchase activity
        $activityLogger->log('CHECKOUT', sprintf(
            'User purchased: %s | Total: ₱%s | Orders: %d',
            implode(', ', $purchasedGames),
            number_format($totalAmount, 2),
            count($createdOrders)
        ));

        $response = [
            'message' => 'Order(s) created successfully',
            'orders' => array_map(function($order) {
                return [
                    'id' => $order->getId(),
                    'orderNumber' => $order->getOrderNumber(),
                    'totalAmount' => $order->getTotalAmount(),
                    'status' => $order->getStatus(),
                    'gameTitle' => $order->getGame()->getTitle(),
                ];
            }, $createdOrders)
        ];
        
        if (!empty($alreadyPurchased)) {
            $response['warning'] = 'You already own: ' . implode(', ', $alreadyPurchased);
        }
        
        return $this->json($response, 201);
    }

    #[Route('', name: 'api_orders_list', methods: ['GET'])]
    public function getOrders(EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $orders = $em->getRepository(Order::class)->findBy(
            ['customer' => $user],
            ['id' => 'DESC']
        );

        $orderData = [];
        foreach ($orders as $order) {
            $orderData[] = [
                'id' => $order->getId(),
                'orderNumber' => $order->getOrderNumber(),
                'quantity' => $order->getQuantity(),
                'totalAmount' => $order->getTotalAmount(),
                'status' => $order->getStatus(),
                'gameTitle' => $order->getGame()?->getTitle(),
            ];
        }

        return $this->json($orderData);
    }

    // ✅ Updated: /library route with full image URLs and license keys
    #[Route('/library', name: 'api_user_library', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getUserLibrary(EntityManagerInterface $em, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Get the base URL for full image paths
        $baseUrl = $request->getSchemeAndHttpHost();
        
        $orders = $em->getRepository(Order::class)->findBy([
            'customer' => $user,
            'status' => 'Completed'
        ]);
        
        $library = [];
        $gameIds = [];
        
        foreach ($orders as $order) {
            $game = $order->getGame();
            if ($game && !in_array($game->getId(), $gameIds)) {
                $gameIds[] = $game->getId();
                
                // Build full image URL
                $imageFilename = $game->getImage();
                $imageUrl = $imageFilename ? $baseUrl . '/images/games/' . $imageFilename : null;
                
                // Get license key from this order
                $licenseKey = null;
                foreach ($order->getLicenseKeys() as $key) {
                    $licenseKey = $key->getCode();
                    break;
                }
                
                $library[] = [
                    'id' => $game->getId(),
                    'title' => $game->getTitle(),
                    'description' => $game->getDescription(),
                    'image' => $imageUrl,
                    'price' => $game->getPrice(),
                    'orderNumber' => $order->getOrderNumber(),
                    'licenseKey' => $licenseKey,
                ];
            }
        }
        
        return $this->json($library);
    }

    // Check if user already owns a game
    #[Route('/check-owned/{gameId}', name: 'api_check_game_owned', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function checkGameOwned(int $gameId, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $existingOrder = $em->getRepository(Order::class)->findOneBy([
            'customer' => $user,
            'game' => $gameId,
            'status' => 'Completed'
        ]);
        
        return $this->json([
            'owned' => $existingOrder !== null
        ]);
    }

    // WILDCARD ROUTE - Must be LAST (catches /{id})
    #[Route('/{id}', name: 'api_orders_detail', methods: ['GET'])]
    public function getOrderDetail(int $id, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $order = $em->getRepository(Order::class)->findOneBy([
            'id' => $id,
            'customer' => $user
        ]);

        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        // Safely get game data
        $game = $order->getGame();
        $gameData = null;
        if ($game) {
            $gameData = [
                'id' => $game->getId(),
                'title' => $game->getTitle(),
                'description' => $game->getDescription(),
                'image' => $game->getImage(),
            ];
        }

        // Get license keys
        $licenseKeys = [];
        foreach ($order->getLicenseKeys() as $key) {
            $licenseKeys[] = [
                'code' => $key->getCode(),
                'status' => $key->getStatus(),
            ];
        }

        return $this->json([
            'id' => $order->getId(),
            'orderNumber' => $order->getOrderNumber(),
            'quantity' => $order->getQuantity(),
            'totalAmount' => $order->getTotalAmount(),
            'status' => $order->getStatus(),
            'game' => $gameData,
            'licenseKeys' => $licenseKeys,
        ]);
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function logout(ActivityLogger $activityLogger): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $activityLogger->log('LOGOUT', $user);
        
        return $this->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }

    // ✅ FCM V1 API: Send push notification using service account JSON
    private function sendPushNotificationV1(string $fcmToken, string $title, string $body, array $data = []): void
    {
        if (empty($fcmToken)) {
            return;
        }
        
        // Path to your Firebase service account JSON file
        $serviceAccountPath = $this->getParameter('kernel.project_dir') . '/config/jwt/inferno-games-app-firebase-adminsdk-fbsvc-8c633a20b6.json';
        
        if (!file_exists($serviceAccountPath)) {
            error_log('FCM Error: Service account JSON file not found at ' . $serviceAccountPath);
            return;
        }
        
        // Get Firebase Project ID from the JSON file
        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
        $projectId = $serviceAccount['project_id'] ?? null;
        
        if (!$projectId) {
            error_log('FCM Error: Could not extract project_id from service account JSON');
            return;
        }
        
        // Generate OAuth2 token using JWT
        $accessToken = $this->getAccessTokenFromServiceAccount($serviceAccountPath);
        
        if (!$accessToken) {
            error_log('FCM Error: Failed to generate access token');
            return;
        }
        
        // Prepare FCM V1 API payload
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        
        $payload = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
                'android' => [
                    'priority' => 'high',
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                ],
            ],
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log('FCM Error: HTTP ' . $httpCode . ' - ' . $response);
        }
    }
    
    // Helper method to generate OAuth2 access token from service account JSON
    private function getAccessTokenFromServiceAccount(string $jsonPath): ?string
    {
        try {
            $serviceAccount = json_decode(file_get_contents($jsonPath), true);
            
            $now = time();
            $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $jwtPayload = base64_encode(json_encode([
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now,
            ]));
            
            $signature = '';
            $privateKey = openssl_get_privatekey($serviceAccount['private_key']);
            openssl_sign($jwtHeader . '.' . $jwtPayload, $signature, $privateKey, 'SHA256');
            $jwt = $jwtHeader . '.' . $jwtPayload . '.' . base64_encode($signature);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $tokenData = json_decode($response, true);
            
            return $tokenData['access_token'] ?? null;
            
        } catch (\Exception $e) {
            error_log('FCM Token Error: ' . $e->getMessage());
            return null;
        }
    }
}
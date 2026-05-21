<?php

namespace App\Controller\Customer;

use App\Entity\GameManagement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/cart')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    private const CART_KEY = 'user_cart';

    #[Route('', name: 'api_cart_get', methods: ['GET'])]
    public function getCart(SessionInterface $session): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $cart = $session->get(self::CART_KEY . '_' . $user->getId(), []);
        
        return $this->json([
            'items' => array_values($cart),
            'totalItems' => array_sum(array_column($cart, 'quantity')),
            'totalAmount' => array_sum(array_column($cart, 'subtotal')),
        ]);
    }

    #[Route('/add', name: 'api_cart_add', methods: ['POST'])]
    public function addToCart(
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['gameId']) || !isset($data['quantity'])) {
            return $this->json(['error' => 'gameId and quantity required'], 400);
        }

        $game = $em->getRepository(GameManagement::class)->find($data['gameId']);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], 404);
        }

        $cart = $session->get(self::CART_KEY . '_' . $user->getId(), []);
        $gameId = (string) $data['gameId'];

        if (isset($cart[$gameId])) {
            $cart[$gameId]['quantity'] += $data['quantity'];
            $cart[$gameId]['subtotal'] = $cart[$gameId]['quantity'] * (float) $game->getPrice();
        } else {
            $cart[$gameId] = [
                'gameId' => $game->getId(),
                'title' => $game->getTitle(),
                'price' => (float) $game->getPrice(),
                'quantity' => $data['quantity'],
                'subtotal' => $data['quantity'] * (float) $game->getPrice(),
                'image' => $game->getImage(),
            ];
        }

        $session->set(self::CART_KEY . '_' . $user->getId(), $cart);

        return $this->json([
            'message' => 'Game added to cart',
            'cart' => [
                'items' => array_values($cart),
                'totalItems' => array_sum(array_column($cart, 'quantity')),
                'totalAmount' => array_sum(array_column($cart, 'subtotal')),
            ]
        ]);
    }

    #[Route('/update/{gameId}', name: 'api_cart_update', methods: ['PUT'])]
    public function updateCartItem(
        int $gameId,
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['quantity']) || $data['quantity'] <= 0) {
            return $this->json(['error' => 'Invalid quantity'], 400);
        }

        $game = $em->getRepository(GameManagement::class)->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], 404);
        }

        $cart = $session->get(self::CART_KEY . '_' . $user->getId(), []);
        $gameIdKey = (string) $gameId;

        if (!isset($cart[$gameIdKey])) {
            return $this->json(['error' => 'Game not in cart'], 404);
        }

        $cart[$gameIdKey]['quantity'] = $data['quantity'];
        $cart[$gameIdKey]['subtotal'] = $data['quantity'] * (float) $game->getPrice();
        $session->set(self::CART_KEY . '_' . $user->getId(), $cart);

        return $this->json([
            'message' => 'Cart updated',
            'cart' => [
                'items' => array_values($cart),
                'totalItems' => array_sum(array_column($cart, 'quantity')),
                'totalAmount' => array_sum(array_column($cart, 'subtotal')),
            ]
        ]);
    }

    #[Route('/remove/{gameId}', name: 'api_cart_remove', methods: ['DELETE'])]
    public function removeFromCart(int $gameId, SessionInterface $session): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $cart = $session->get(self::CART_KEY . '_' . $user->getId(), []);
        $gameIdKey = (string) $gameId;

        if (!isset($cart[$gameIdKey])) {
            return $this->json(['error' => 'Game not in cart'], 404);
        }

        unset($cart[$gameIdKey]);
        $session->set(self::CART_KEY . '_' . $user->getId(), $cart);

        return $this->json([
            'message' => 'Game removed from cart',
            'cart' => [
                'items' => array_values($cart),
                'totalItems' => array_sum(array_column($cart, 'quantity')),
                'totalAmount' => array_sum(array_column($cart, 'subtotal')),
            ]
        ]);
    }

    #[Route('/clear', name: 'api_cart_clear', methods: ['DELETE'])]
    public function clearCart(SessionInterface $session): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $session->set(self::CART_KEY . '_' . $user->getId(), []);
        
        return $this->json(['message' => 'Cart cleared']);
    }
}
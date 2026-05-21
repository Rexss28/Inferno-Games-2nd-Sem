<?php

namespace App\Controller\Customer;

use App\Entity\GameManagement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/games')]
class GameController extends AbstractController
{
    #[Route('', name: 'api_games_list', methods: ['GET'])]
    public function getGames(EntityManagerInterface $em, Request $request): JsonResponse
    {
        $games = $em->getRepository(GameManagement::class)->findAll();
        
        // Get the base URL (e.g., http://localhost:8000)
        $baseUrl = $request->getSchemeAndHttpHost();
        
        $gameData = [];
        foreach ($games as $game) {
            $imageFilename = $game->getImage();
            
            // Build full image URL - images are stored in /images/games/
            $imageUrl = null;
            if ($imageFilename) {
                $imageUrl = $baseUrl . '/images/games/' . $imageFilename;
            }
            
            $gameData[] = [
                'id' => $game->getId(),
                'title' => $game->getTitle(),
                'price' => $game->getPrice(),
                'description' => $game->getDescription(),
                'image' => $imageUrl,
            ];
        }
        
        return $this->json($gameData);
    }

    #[Route('/{id}', name: 'api_games_detail', methods: ['GET'])]
    public function getGameDetail(int $id, EntityManagerInterface $em, Request $request): JsonResponse
    {
        $game = $em->getRepository(GameManagement::class)->find($id);
        
        if (!$game) {
            return $this->json(['error' => 'Game not found'], 404);
        }
        
        $baseUrl = $request->getSchemeAndHttpHost();
        $imageFilename = $game->getImage();
        $imageUrl = $imageFilename ? $baseUrl . '/images/games/' . $imageFilename : null;
        
        return $this->json([
            'id' => $game->getId(),
            'title' => $game->getTitle(),
            'price' => $game->getPrice(),
            'description' => $game->getDescription(),
            'image' => $imageUrl,
        ]);
    }
}
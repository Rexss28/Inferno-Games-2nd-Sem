<?php

namespace App\Controller;

use App\Entity\GameManagement;
use App\Entity\Stock;
use App\Form\GameManagementType;
use App\Repository\GameManagementRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GameManagementController extends AbstractController
{
    #[Route('/game/management', name: 'app_game_management_index', methods: ['GET'])]
    public function index(GameManagementRepository $gameManagementRepository): Response
    {
        return $this->render('Admin/game_management/index.html.twig', [
            'game_managements' => $gameManagementRepository->findAll(),
        ]);
    }

    #[Route('/game/management/new', name: 'app_game_management_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ActivityLogger $logger, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $game = new GameManagement();
        
        // Get available stocks for the dropdown
        $stocks = $entityManager->getRepository(Stock::class)->findAll();
        
        if ($request->isMethod('POST')) {
            // Get form data
            $formData = $request->request->all()['game_management'] ?? [];
            
            // Set basic fields
            $game->setTitle($formData['title'] ?? '');
            $game->setPrice($formData['price'] ?? '0.00');
            $game->setDescription($formData['description'] ?? '');
            
            // Handle stock if provided
            $stockId = $formData['stock'] ?? null;
            if ($stockId && $stockId !== '') {
                $stock = $entityManager->getRepository(Stock::class)->find($stockId);
                if ($stock) {
                    $game->setStock($stock);
                }
            }
            
            $game->setCreatedBy($this->getUser());

            // Handle image upload
            $imageFile = $request->files->get('game_management')['image'] ?? null;
            if ($imageFile && $imageFile->getClientOriginalName() !== '') {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('games_image_directory'),
                        $newFilename
                    );
                    $game->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload failed: ' . $e->getMessage());
                }
            }

            // Validate the game entity using injected validator
            $errors = $validator->validate($game);
            
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                try {
                    $entityManager->persist($game);
                    $entityManager->flush();

                    $logger->log('CREATE', $game);
                    $this->addFlash('success', 'Game added successfully!');
                    return $this->redirectToRoute('app_game_management_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to save game: ' . $e->getMessage());
                }
            }
        }

        return $this->render('Admin/game_management/new.html.twig', [
            'game_management' => $game,
            'stocks' => $stocks,
        ]);
    }

    #[Route('/game/management/{id}', name: 'app_game_management_show', methods: ['GET'])]
    public function show(GameManagement $gameManagement): Response
    {
        return $this->render('Admin/game_management/show.html.twig', [
            'game_management' => $gameManagement,
        ]);
    }

    #[Route('/game/management/{id}/edit', name: 'app_game_management_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, GameManagement $game, EntityManagerInterface $entityManager, SluggerInterface $slugger, ActivityLogger $logger): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $game);

        $form = $this->createForm(GameManagementType::class, $game, [
            'require_image' => false,
        ]);
        
        $form->handleRequest($request);

        $oldImage = $game->getImage();

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('games_image_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload failed.');
                }

                if ($oldImage && file_exists($this->getParameter('games_image_directory') . '/' . $oldImage)) {
                    unlink($this->getParameter('games_image_directory') . '/' . $oldImage);
                }

                $game->setImage($newFilename);
            } else {
                $game->setImage($oldImage);
            }

            $entityManager->flush();

            $logger->log('UPDATE', $game);
            $this->addFlash('success', 'Game updated successfully!');
            return $this->redirectToRoute('app_game_management_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Admin/game_management/edit.html.twig', [
            'game_management' => $game,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/game/management/{id}', name: 'app_game_management_delete', methods: ['POST'])]
    public function delete(Request $request, GameManagement $gameManagement, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $this->denyAccessUnlessGranted('DELETE', $gameManagement);

        if ($this->isCsrfTokenValid('delete'.$gameManagement->getId(), $request->request->get('_token'))) {
            $gameName = $gameManagement->getTitle();
            $gameId = $gameManagement->getId();

            // Remove image file if exists
            $image = $gameManagement->getImage();
            if ($image && file_exists($this->getParameter('games_image_directory') . '/' . $image)) {
                unlink($this->getParameter('games_image_directory') . '/' . $image);
            }

            // Clear relationships
            foreach ($gameManagement->getLicenseKeys() as $licenseKey) {
                $licenseKey->setGame(null);
            }

            foreach ($gameManagement->getOrders() as $order) {
                $order->setGame(null);
            }

            $stock = $gameManagement->getStock();
            if ($stock) {
                $stock->setGame(null);
            }

            $entityManager->remove($gameManagement);
            $entityManager->flush();

            $logger->log('DELETE', 'Game: ' . $gameName . ' (ID: ' . $gameId . ')');
            $this->addFlash('success', 'Game deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_game_management_index', [], Response::HTTP_SEE_OTHER);
    }
}
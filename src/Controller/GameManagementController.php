<?php

namespace App\Controller;

use App\Entity\GameManagement;
use App\Form\GameManagementType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Repository\GameManagementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/game/management')]
final class GameManagementController extends AbstractController
{
    #[Route(name: 'app_game_management_index', methods: ['GET'])]
    public function index(GameManagementRepository $gameManagementRepository): Response
    {
        return $this->render('Admin/game_management/index.html.twig', [
            'game_managements' => $gameManagementRepository->findAll(),
        ]);
    }

   #[Route('/new', name: 'app_game_management_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
{
    $game = new GameManagement();
    $form = $this->createForm(GameManagementType::class, $game);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Handle image upload
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

            $game->setImage($newFilename);
        }

        $entityManager->persist($game);
        $entityManager->flush();

        $this->addFlash('success', 'Game added successfully!');
        return $this->redirectToRoute('app_game_management_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('Admin/game_management/new.html.twig', [
        'game_management' => $game,
        'form' => $form,
    ]);
}

    #[Route('/{id}', name: 'app_game_management_show', methods: ['GET'])]
    public function show(GameManagement $gameManagement): Response
    {
        return $this->render('Admin/game_management/show.html.twig', [
            'game_management' => $gameManagement,
        ]);
    }

   #[Route('/{id}/edit', name: 'app_game_management_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, GameManagement $game, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
{
    $form = $this->createForm(GameManagementType::class, $game);
    $form->handleRequest($request);

    $oldImage = $game->getImage(); // Store current image filename

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            // Generate a safe new filename
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

            // Delete the old image file if it exists
            if ($oldImage && file_exists($this->getParameter('games_image_directory') . '/' . $oldImage)) {
                unlink($this->getParameter('games_image_directory') . '/' . $oldImage);
            }

            // Update with new filename
            $game->setImage($newFilename);
        } else {
            // No new file uploaded → keep the old image
            $game->setImage($oldImage);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Game updated successfully!');
        return $this->redirectToRoute('app_game_management_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('Admin/game_management/edit.html.twig', [
        'game_management' => $game,
        'form' => $form,
    ]);
}


    #[Route('/{id}', name: 'app_game_management_delete', methods: ['POST'])]
    public function delete(Request $request, GameManagement $gameManagement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$gameManagement->getId(), $request->getPayload()->getString('_token'))) {

            // 1️⃣ Unlink related License Keys
            foreach ($gameManagement->getLicenseKeys() as $licenseKey) {
                $licenseKey->setGame(null);
            }

            // 2️⃣ Unlink related Orders
            foreach ($gameManagement->getOrders() as $order) {
                $order->setGame(null);
            }

            // 3️⃣ Unlink related Stock
            $stock = $gameManagement->getStock();
            if ($stock) {
                $stock->setGame(null);
            }

            // 4️⃣ Now you can safely delete the Game
            $entityManager->remove($gameManagement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_game_management_index', [], Response::HTTP_SEE_OTHER);
    }

}



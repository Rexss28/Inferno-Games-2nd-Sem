<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Form\StockType;
use App\Repository\StockRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stock')]
final class StockController extends AbstractController
{
    #[Route(path: '/', name: 'app_stock_index', methods: ['GET'])]
    public function index(StockRepository $stockRepository): Response
    {
        return $this->render('Admin/stock/index.html.twig', [
            'stocks' => $stockRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $stock = new Stock();
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $stock->setCreatedBy($this->getUser());
            $stock->updateStatusAutomatically();
            $entityManager->persist($stock);
            $entityManager->flush();

            $logger->log('CREATE', $stock);
            $this->addFlash('success', 'Stock created successfully!');
            return $this->redirectToRoute('app_stock_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Admin/stock/new.html.twig', [
            'stock' => $stock,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_stock_show', methods: ['GET'])]
    public function show(Stock $stock): Response
    {
        return $this->render('Admin/stock/show.html.twig', [
            'stock' => $stock,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_stock_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Stock $stock, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $stock);

        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $stock->updateStatusAutomatically();
            $entityManager->flush();

            $logger->log('UPDATE', $stock);
            $this->addFlash('success', 'Stock updated successfully!');
            return $this->redirectToRoute('app_stock_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Admin/stock/edit.html.twig', [
            'stock' => $stock,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_stock_delete', methods: ['POST'])]  // CHANGED: Added /delete
    public function delete(Request $request, Stock $stock, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $this->denyAccessUnlessGranted('DELETE', $stock);

        if ($this->isCsrfTokenValid('delete'.$stock->getId(), $request->getPayload()->getString('_token'))) {
            $game = $stock->getGame();
            
            // Create log message before deletion
            $logMessage = sprintf(
                'Stock #%d (Available: %d/%d, Status: %s)',
                $stock->getId(),
                $stock->getAvailableQuantity(),
                $stock->getTotalQuantity(),
                $stock->getStatus()
            );

            if ($game) {
                $game->setStock(null);
            }

            $entityManager->remove($stock);
            $entityManager->flush();

            $logger->log('DELETE', $logMessage);
            $this->addFlash('success', 'Stock deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_stock_index', [], Response::HTTP_SEE_OTHER);
    }
}
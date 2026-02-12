<?php

namespace App\Controller;

use App\Entity\LicenseKey;
use App\Form\LicenseKeyType;
use App\Repository\LicenseKeyRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/license/key')]
final class LicenseKeyController extends AbstractController
{
    #[Route(path: '/', name: 'app_license_key_index', methods: ['GET'])]  // ADD path: '/'
    public function index(LicenseKeyRepository $licenseKeyRepository): Response
    {
        return $this->render('Admin/license_key/index.html.twig', [
            'license_keys' => $licenseKeyRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_license_key_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $licenseKey = new LicenseKey();
        $form = $this->createForm(LicenseKeyType::class, $licenseKey);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $licenseKey->setCreatedBy($this->getUser());
            $licenseKey->updateStatusAutomatically();
            $entityManager->persist($licenseKey);
            $entityManager->flush();

            $logger->log('CREATE', $licenseKey);
            $this->addFlash('success', 'License key created successfully!');
            return $this->redirectToRoute('app_license_key_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Admin/license_key/new.html.twig', [
            'license_key' => $licenseKey,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_license_key_show', methods: ['GET'])]
    public function show(LicenseKey $licenseKey): Response
    {
        return $this->render('Admin/license_key/show.html.twig', [
            'license_key' => $licenseKey,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_license_key_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, LicenseKey $licenseKey, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $licenseKey);

        $form = $this->createForm(LicenseKeyType::class, $licenseKey);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $licenseKey->updateStatusAutomatically();
            $entityManager->flush();

            $logger->log('UPDATE', $licenseKey);
            $this->addFlash('success', 'License key updated successfully!');
            return $this->redirectToRoute('app_license_key_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Admin/license_key/edit.html.twig', [
            'license_key' => $licenseKey,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_license_key_delete', methods: ['POST'])]
    public function delete(Request $request, LicenseKey $licenseKey, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $this->denyAccessUnlessGranted('DELETE', $licenseKey);

        if ($this->isCsrfTokenValid('delete'.$licenseKey->getId(), $request->getPayload()->getString('_token'))) {
            $licenseKeyCode = $licenseKey->getCode();
            $licenseKeyId = $licenseKey->getId();

            // Unlink from game if needed
            $game = $licenseKey->getGame();
            if ($game) {
                $game->removeLicenseKey($licenseKey);
            }
            
            // Unlink from order if needed
            $order = $licenseKey->getOrder();
            if ($order) {
                $licenseKey->setOrder(null);
            }
            
            $entityManager->remove($licenseKey);
            $entityManager->flush();

            $logger->log('DELETE', 'LicenseKey: ' . $licenseKeyCode . ' (ID: ' . $licenseKeyId . ')');
            $this->addFlash('success', 'License key deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_license_key_index', [], Response::HTTP_SEE_OTHER);
    }
}
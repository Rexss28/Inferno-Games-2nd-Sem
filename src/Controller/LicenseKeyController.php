<?php

namespace App\Controller;

use App\Entity\LicenseKey;
use App\Form\LicenseKeyType;
use App\Repository\LicenseKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/license/key')]
final class LicenseKeyController extends AbstractController
{
    #[Route(name: 'app_license_key_index', methods: ['GET'])]
    public function index(LicenseKeyRepository $licenseKeyRepository): Response
    {
        return $this->render('Admin/license_key/index.html.twig', [
            'license_keys' => $licenseKeyRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_license_key_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $licenseKey = new LicenseKey();
        $form = $this->createForm(LicenseKeyType::class, $licenseKey);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $licenseKey->updateStatusAutomatically();
            $entityManager->persist($licenseKey);
            $entityManager->flush();

            return $this->redirectToRoute('app_license_key_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Admin/license_key/new.html.twig', [
            'license_key' => $licenseKey,
            'form' => $form,
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
    public function edit(Request $request, LicenseKey $licenseKey, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LicenseKeyType::class, $licenseKey);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $licenseKey->updateStatusAutomatically();
            $entityManager->flush();

            return $this->redirectToRoute('app_license_key_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Admin/license_key/edit.html.twig', [
            'license_key' => $licenseKey,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_license_key_delete', methods: ['POST'])]
    public function delete(Request $request, LicenseKey $licenseKey, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$licenseKey->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($licenseKey);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_license_key_index', [], Response::HTTP_SEE_OTHER);
    }
}

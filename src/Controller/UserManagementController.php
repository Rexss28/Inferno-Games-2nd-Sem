<?php

namespace App\Controller;

use App\Entity\UserManagement;
use App\Form\UserManagementType;
use App\Repository\UserManagementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/management')]
final class UserManagementController extends AbstractController
{
    #[Route(name: 'app_user_management_index', methods: ['GET'])]
    public function index(UserManagementRepository $userManagementRepository): Response
    {
        return $this->render('Admin/user_management/index.html.twig', [
            'user_managements' => $userManagementRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_management_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userManagement = new UserManagement();
        $form = $this->createForm(UserManagementType::class, $userManagement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($userManagement);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_management_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Admin/user_management/new.html.twig', [
            'user_management' => $userManagement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_management_show', methods: ['GET'])]
    public function show(UserManagement $userManagement): Response
    {
        return $this->render('Admin/user_management/show.html.twig', [
            'user_management' => $userManagement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_management_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserManagement $userManagement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserManagementType::class, $userManagement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_management_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Admin/user_management/edit.html.twig', [
            'user_management' => $userManagement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_management_delete', methods: ['POST'])]
    public function delete(Request $request, UserManagement $userManagement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$userManagement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($userManagement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_management_index', [], Response::HTTP_SEE_OTHER);
    }
}

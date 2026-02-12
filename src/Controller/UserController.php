<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(path: '/', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        $statusCounts = [
            'active' => $userRepository->count(['status' => User::STATUS_ACTIVE]),
            'inactive' => $userRepository->count(['status' => User::STATUS_INACTIVE]),
        ];

        return $this->render('Admin/user/index.html.twig', [
            'users' => $users,
            'statusCounts' => $statusCounts,
        ]);
    }

    #[Route('/inactive', name: 'app_user_inactive', methods: ['GET'])]
    public function inactiveUsers(UserRepository $userRepository): Response
    {
        $inactiveUsers = $userRepository->findBy(['status' => User::STATUS_INACTIVE], ['inactivatedAt' => 'DESC']);

        return $this->render('Admin/user/inactive.html.twig', [
            'users' => $inactiveUsers,
            'statusCounts' => [
                'active' => $userRepository->count(['status' => User::STATUS_ACTIVE]),
                'inactive' => $userRepository->count(['status' => User::STATUS_INACTIVE]),
            ],
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ActivityLogger $logger
    ): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle password for user creation
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            // Set status timestamps if needed
            $status = $form->get('status')->getData();
            if ($status === User::STATUS_INACTIVE) {
                $user->setInactivatedAt(new \DateTimeImmutable());
            }

            $entityManager->persist($user);
            $entityManager->flush();

            // LOG THE USER CREATION
            $logger->log('CREATE', $user);

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('Admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ActivityLogger $logger
    ): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ONLY hash password if a new one was entered
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            // Handle status changes
            $oldStatus = $user->getStatus();
            $newStatus = $form->get('status')->getData();
            
            if ($oldStatus !== $newStatus) {
                if ($newStatus === User::STATUS_INACTIVE) {
                    $user->setInactivatedAt(new \DateTimeImmutable());
                } elseif ($newStatus === User::STATUS_ACTIVE) {
                    $user->setInactivatedAt(null);
                    $user->setStatusReason(null);
                }
            }

            $entityManager->flush();

            // LOG THE USER UPDATE
            $logger->log('UPDATE', $user, "Status changed from {$oldStatus} to {$newStatus}");

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/status/{status}', name: 'app_user_status', methods: ['POST'])]
    public function status(
        Request $request,
        User $user,
        string $status,
        EntityManagerInterface $entityManager,
        ActivityLogger $logger
    ): Response
    {
        if ($this->isCsrfTokenValid('status' . $user->getId(), $request->request->get('_token'))) {
            $oldStatus = $user->getStatus();
            
            if ($status === 'inactivate') {
                $reason = $request->request->get('reason', 'Archived/Disabled by admin');
                $user->inactivate($reason);
            } elseif ($status === 'activate') {
                $user->activate();
            }

            $entityManager->flush();

            // LOG THE STATUS CHANGE
            $logger->log('STATUS', $user, "Status changed from {$oldStatus} to {$user->getStatus()}");
            
            $this->addFlash('success', "User {$user->getUsername()} status updated to {$user->getStatus()}.");
        }

        // FIXED: Redirect back to the previous page (Active Users page)
        $referer = $request->headers->get('referer');
        if ($referer) {
            return new RedirectResponse($referer);
        }
        
        // Fallback: redirect to active users page
        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $username = $user->getUsername();
            $userId = $user->getId();
            
            $entityManager->remove($user);
            $entityManager->flush();

            // LOG THE USER DELETION
            $logger->log('DELETE', 'User: ' . $username . ' (ID: ' . $userId . ')');
            
            $this->addFlash('success', "User {$username} has been permanently deleted.");
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/restore', name: 'app_user_restore', methods: ['POST'])]
    public function restore(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        ActivityLogger $logger
    ): Response
    {
        // Check CSRF token
        if ($this->isCsrfTokenValid('restore' . $user->getId(), $request->request->get('_token'))) {
            // Activate the user
            $user->activate();
            $user->setStatusReason('Restored by admin');
            
            $entityManager->flush();

            // LOG THE RESTORATION
            $logger->log('RESTORE', $user);
            
            $this->addFlash('success', "User {$user->getUsername()} has been restored to active status.");
            
            // Redirect to ACTIVE USERS page (not inactive)
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        } else {
            $this->addFlash('error', 'Invalid security token.');
        }

        // Fallback: redirect back to inactive page
        return $this->redirectToRoute('app_user_inactive', [], Response::HTTP_SEE_OTHER);
    }
}
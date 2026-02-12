<?php
// src/Controller/ProfileController.php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/profile')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig');
    }

    #[Route('/update-username', name: 'profile_update_username', methods: ['POST'])]
    public function updateUsername(
        Request $request, 
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        $newUsername = $request->request->get('newUsername');
        
        // Basic validation
        if (empty($newUsername)) {
            $this->addFlash('error', 'Username cannot be empty.');
            return $this->redirectToRoute('app_profile_index');
        }
        
        // Validate username length
        if (strlen($newUsername) < 3 || strlen($newUsername) > 50) {
            $this->addFlash('error', 'Username must be between 3 and 50 characters.');
            return $this->redirectToRoute('app_profile_index');
        }
        
        // Validate username pattern
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $newUsername)) {
            $this->addFlash('error', 'Username can only contain letters, numbers, and underscores.');
            return $this->redirectToRoute('app_profile_index');
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Check if username is same as current
        if ($newUsername === $user->getUsername()) {
            $this->addFlash('error', 'New username cannot be the same as your current username.');
            return $this->redirectToRoute('app_profile_index');
        }
        
        // Check if username already exists
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['username' => $newUsername]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            $this->addFlash('error', 'This username is already taken.');
            return $this->redirectToRoute('app_profile_index');
        }
        
        // Update username
        $user->setUsername($newUsername);
        $entityManager->flush();
        
        $this->addFlash('success', 'Username updated successfully! You will need to use this new username to log in next time.');
        return $this->redirectToRoute('app_profile_index');
    }

    #[Route('/update-password', name: 'profile_update_password', methods: ['POST'])]
    public function updatePassword(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $newPassword = $request->request->get('newPassword');
        $confirmPassword = $request->request->get('confirmPassword');
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Check if new passwords match
        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'New passwords do not match.');
            return $this->redirectToRoute('app_profile_index');
        }
        
        // Validate password length
        if (strlen($newPassword) < 6) {
            $this->addFlash('error', 'Password must be at least 6 characters long.');
            return $this->redirectToRoute('app_profile_index');
        }
        
        // Update password
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $entityManager->flush();
        
        $this->addFlash('success', 'Password updated successfully!');
        return $this->redirectToRoute('app_profile_index');
    }
}
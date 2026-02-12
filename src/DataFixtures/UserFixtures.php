<?php

namespace App\DataFixtures;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;


class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $repo = $manager->getRepository(User::class);

        // create admin only if missing
        if (!$repo->findOneBy(['username' => 'admin'])) {
            $admin = new User();
            $admin->setUsername('admin');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
            $manager->persist($admin);
        }

        // create staff only if missing
        if (!$repo->findOneBy(['username' => 'staff'])) {
            $staff = new User();
            $staff->setUsername('staff');
            $staff->setRoles(['ROLE_STAFF']);
            $staff->setPassword($this->passwordHasher->hashPassword($staff, 'staff123'));
            $manager->persist($staff);
        }

        $manager->flush();
    }
}

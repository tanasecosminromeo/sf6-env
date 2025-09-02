<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setName('Cosmin Romeo TANASE');
        $user->setEmail('cosmin@tanase.dev');
        $user->setUsername('crtanase');

        $password = $this->userPasswordHasher->hashPassword($user, 'mysecretpassword2025');
        $user->setPassword($password);

        $manager->persist($user);
        $manager->flush();
    }
}

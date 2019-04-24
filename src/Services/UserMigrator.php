<?php

namespace App\Services;

use App\Entity\User;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FOS\UserBundle\Util\UserManipulator;
use Symfony\Component\Console\Output\OutputInterface;

class UserMigrator
{
    private $userService;
    private $userManipulator;

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $repository;

    private $userData;

    public function __construct(
        UserDataProvider $defaultUserData,
        EntityManagerInterface $entityManager,
        UserService $userService,
        UserManipulator $userManipulator
    ) {
        $this->userData = $defaultUserData->getData();
        $this->repository = $entityManager->getRepository(User::class);
        $this->userService = $userService;
        $this->userManipulator = $userManipulator;
    }

    public function migrate(?OutputInterface $output = null)
    {
        if ($output) {
            $output->writeln('Migrating default users ...');
        }

        foreach ($this->userData as $userData) {
            $this->migrateUser($userData, $output);
        }

        if ($output) {
            $output->writeln('');
        }
    }

    private function migrateUser(array $userData, ?OutputInterface $output = null)
    {
        if ($output) {
            $output->writeln('');
        }

        $email = $userData['email'];
        $username = $userData['username'];
        $password = $userData['password'];
        $role = $userData['role'];

        if ($output) {
            $output->writeln("  " . '<comment>' . $email . '</comment>');
        }

        $user = $this->repository->findOneBy([
            'email' => $email,
        ]);

        if (is_null($user)) {
            if ($output) {
                $output->writeln('  <fg=cyan>creating</>');
            }

            $user = new User();
            $user->setEmail($email);
            $user->setPlainPassword($password);
            $user->setUsername($username);

            if ($role) {
                $user->addRole($role);
            }

            $this->userService->updateUser($user);
            $this->userManipulator->activate($user->getUsername());
        }
    }
}

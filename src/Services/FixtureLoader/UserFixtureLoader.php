<?php

namespace App\Services\FixtureLoader;

use App\Entity\User;
use App\Services\UserDataProvider;
use App\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Util\UserManipulator;
use Symfony\Component\Console\Output\OutputInterface;

class UserFixtureLoader extends AbstractFixtureLoader implements FixtureLoaderInterface
{
    private $userService;
    private $userManipulator;
    private $userData;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserDataProvider $defaultUserData,
        UserService $userService,
        UserManipulator $userManipulator
    ) {
        parent::__construct($entityManager);

        $this->userData = $defaultUserData->getData();
        $this->userService = $userService;
        $this->userManipulator = $userManipulator;
    }

    protected function getEntityClass(): string
    {
        return User::class;
    }

    public function load(?OutputInterface $output = null): void
    {
        if ($output) {
            $output->writeln('Migrating default users ...');
        }

        foreach ($this->userData as $userData) {
            $this->loadUser($userData, $output);
        }

        if ($output) {
            $output->writeln('');
        }
    }

    private function loadUser(array $userData, ?OutputInterface $output = null)
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

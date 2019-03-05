<?php

namespace App\DataFixtures\ORM;

use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\UserBundle\Util\UserManipulator;
use App\Services\UserService;
use App\Entity\User;

class LoadUserData extends Fixture
{
    const PUBLIC_USER_EMAIL = 'public@simplytestable.com';
    const PUBLIC_USER_USERNAME = 'public';
    const PUBLIC_USER_PASSWORD = 'public';
    const ADMIN_USER_USERNAME = 'admin';

    private $userService;
    private $userManipulator;
    private $userRepository;

    /**
     * @var string
     */
    private $adminUserEmail;

    /**
     * @var string
     */
    private $adminUserPassword;

    public function __construct(
        UserService $userService,
        UserManipulator $userManipulator,
        UserRepository $userRepository,
        string $adminUserEmail,
        string $adminUserPassword
    ) {
        $this->userService = $userService;
        $this->userManipulator = $userManipulator;
        $this->userRepository = $userRepository;
        $this->adminUserEmail = $adminUserEmail;
        $this->adminUserPassword = $adminUserPassword;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $publicUser = $this->userRepository->findOneBy([
            'email' => self::PUBLIC_USER_EMAIL,
        ]);

        if (empty($publicUser)) {
            $user = new User();
            $user->setEmail(self::PUBLIC_USER_EMAIL);
            $user->setPlainPassword(self::PUBLIC_USER_PASSWORD);
            $user->setUsername(self::PUBLIC_USER_USERNAME);

            $this->userService->updateUser($user);
            $this->userManipulator->activate($user->getUsername());
        }

        $adminUser = $this->userRepository->findOneBy([
            'email' => $this->adminUserEmail,
        ]);

        if (empty($adminUser)) {
            $user = new User();
            $user->setEmail($this->adminUserEmail);
            $user->setPlainPassword($this->adminUserPassword);
            $user->setUsername(self::ADMIN_USER_USERNAME);
            $user->addRole('role_admin');

            $this->userService->updateUser($user);
            $this->userManipulator->activate($user->getUsername());
        }
    }
}

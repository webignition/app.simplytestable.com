<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Util\UserManipulator;
use AppBundle\Services\UserService;
use AppBundle\Entity\User;

class LoadUserData extends Fixture
{
    const PUBLIC_USER_EMAIL = 'public@simplytestable.com';
    const PUBLIC_USER_USERNAME = 'public';
    const PUBLIC_USER_PASSWORD = 'public';
    const ADMIN_USER_USERNAME = 'admin';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var UserManipulator
     */
    private $userManipulator;

    /**
     * @var string
     */
    private $adminUserEmail;

    /**
     * @var string
     */
    private $adminUserPassword;

    /**
     * @param EntityManagerInterface $entityManager
     * @param UserService $userService
     * @param UserManipulator $userManipulator
     * @param string $adminUserEmail
     * @param string $adminUserPassword
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        UserService $userService,
        UserManipulator $userManipulator,
        string $adminUserEmail,
        string $adminUserPassword
    ) {
        $this->entityManager = $entityManager;
        $this->userService = $userService;
        $this->userManipulator = $userManipulator;
        $this->adminUserEmail = $adminUserEmail;
        $this->adminUserPassword = $adminUserPassword;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $userRepository = $this->entityManager->getRepository(User::class);

        $publicUser = $userRepository->findOneBy([
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

        $adminUser = $userRepository->findOneBy([
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

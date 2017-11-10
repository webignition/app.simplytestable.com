<?php

namespace SimplyTestable\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use SimplyTestable\ApiBundle\Entity\User;

class LoadUserData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    const PUBLIC_USER_EMAIL = 'public@simplytestable.com';
    const PUBLIC_USER_USERNAME = 'public';
    const PUBLIC_USER_PASSWORD = 'public';

    const ADMIN_USER_USERNAME = 'admin';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $userManipulator = $this->container->get('fos_user.util.user_manipulator');
        $userRepository = $manager->getRepository(User::class);

        $publicUser = $userRepository->findOneBy([
            'email' => self::PUBLIC_USER_EMAIL,
        ]);

        if (empty($publicUser)) {
            $user = new User();
            $user->setEmail(self::PUBLIC_USER_EMAIL);
            $user->setPlainPassword(self::PUBLIC_USER_PASSWORD);
            $user->setUsername(self::PUBLIC_USER_USERNAME);

            $userService->updateUser($user);
            $userManipulator->activate($user->getUsername());
        }

        $adminUserEmail = $this->container->getParameter('admin_user_email');

        $adminUser = $userRepository->findOneBy([
            'email' => $adminUserEmail,
        ]);

        if (empty($adminUser)) {
            $user = new User();
            $user->setEmail($this->container->getParameter('admin_user_email'));
            $user->setPlainPassword($this->container->getParameter('admin_user_password'));
            $user->setUsername(self::ADMIN_USER_USERNAME);
            $user->addRole('role_admin');

            $userService->updateUser($user);
            $userManipulator->activate($user->getUsername());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 1; // the order in which fixtures will be loaded
    }
}

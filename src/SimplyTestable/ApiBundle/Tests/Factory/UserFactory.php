<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use SimplyTestable\ApiBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $email
     * @param string $password
     *
     * @return User
     */
    public function createAndActivateUser($email, $password)
    {
        $user = $this->create($email, $password);
        $this->activate($user);

        return $user;
    }

    /**
     * @param string $email
     * @param string $planName
     *
     * @return User
     */
    public function create($email = 'user@example.com', $planName = 'basic')
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $accountPlanService = $this->container->get('simplytestable.services.accountplanservice');

        if ($userService->exists($email)) {
            /* @var User $user */
            $user = $userService->findUserByEmail($email);

            return $user;
        }

        $user = $userService->create($email, 'password1');
        $userAccountPlanService->subscribe(
            $user,
            $accountPlanService->find($planName)
        );

        return $user;
    }

    /**
     * @param User $user
     */
    public function activate(User $user)
    {
        $userManipulator = $this->container->get('fos_user.util.user_manipulator');

        $userManipulator->activate($user->getEmail());
    }
}

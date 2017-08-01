<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use SimplyTestable\ApiBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserFactory
{
    const DEFAULT_USER_EMAIL = 'user@example.com';

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
     * @param string $planName
     *
     * @return User
     */
    public function createAndActivateUser($email = self::DEFAULT_USER_EMAIL, $planName = 'basic')
    {
        $user = $this->create($email, $planName);
        $this->activate($user);

        return $user;
    }

    /**
     * @param string $email
     * @param string $planName
     *
     * @return User
     */
    public function create($email = self::DEFAULT_USER_EMAIL, $planName = 'basic')
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $accountPlanService = $this->container->get('simplytestable.services.accountplanservice');

        if ($userService->exists($email)) {
            /* @var User $user */
            $user = $userService->findUserByEmail($email);

            return $user;
        }

        $user = $userService->create($email, 'password');
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

    /**
     * @return User[]
     */
    public function createPublicPrivateAndTeamUserSet()
    {
        $teamService = $this->container->get('simplytestable.services.teamservice');

        $users = array_merge(
            $this->createPublicAndPrivateUserSet(),
            [
                'leader' => $this->createAndActivateUser('leader@example.com'),
                'member1' => $this->createAndActivateUser('member1@example.com'),
                'member2' => $this->createAndActivateUser('member2@example.com'),
            ]
        );

        $team = $teamService->create('Foo', $users['leader']);
        $teamMemberService = $teamService->getMemberService();

        $teamMemberService->add($team, $users['member1']);
        $teamMemberService->add($team, $users['member2']);

        return $users;
    }

    /**
     * @return User[]
     */
    public function createPublicAndPrivateUserSet()
    {
        $userService = $this->container->get('simplytestable.services.userservice');

        $users = [
            'public' => $userService->getPublicUser(),
            'private' => $this->createAndActivateUser('private@example.com'),
        ];

        return $users;
    }
}

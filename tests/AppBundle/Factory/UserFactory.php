<?php

namespace Tests\AppBundle\Factory;

use AppBundle\Entity\User;
use AppBundle\Services\Team\Service;
use AppBundle\Services\UserService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserFactory
{
    const DEFAULT_EMAIL = 'user@example.com';
    const DEFAULT_PLAN_NAME = 'basic';

    const KEY_EMAIL = 'email';
    const KEY_PLAN_NAME = 'plan-name';

    /**
     * @var array
     */
    private $defaultUserValues = [
        self::KEY_EMAIL => self::DEFAULT_EMAIL,
        self::KEY_PLAN_NAME => self::DEFAULT_PLAN_NAME,
    ];

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
     * @param array $userValues
     *
     * @return User
     */
    public function createAndActivateUser($userValues = [])
    {
        $user = $this->create($userValues);
        $user->setEnabled(true);

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    /**
     * @param array $userValues
     *
     * @return User
     */
    public function create($userValues = [])
    {
        $userService = $this->container->get(UserService::class);

        foreach ($this->defaultUserValues as $key => $value) {
            if (!array_key_exists($key, $userValues)) {
                $userValues[$key] = $value;
            }
        }

        if ($userService->exists($userValues[self::KEY_EMAIL])) {
            /* @var User $user */
            $user = $userService->findUserByEmail($userValues[self::KEY_EMAIL]);

            return $user;
        }

        $user = $userService->create($userValues[self::KEY_EMAIL], 'password');

        if (isset($userValues[self::KEY_PLAN_NAME])) {
            $userAccountPlanFactory = new UserAccountPlanFactory($this->container);

            $userAccountPlanFactory->create($user, $userValues[self::KEY_PLAN_NAME]);
        }

        return $user;
    }

    /**
     * @return User[]
     */
    public function createPublicPrivateAndTeamUserSet()
    {
        $teamService = $this->container->get(Service::class);

        $users = array_merge(
            $this->createPublicAndPrivateUserSet(),
            [
                'leader' => $this->createAndActivateUser([
                    self::KEY_EMAIL => 'leader@example.com',
                ]),
                'member1' => $this->createAndActivateUser([
                    self::KEY_EMAIL => 'member1@example.com',
                ]),
                'member2' => $this->createAndActivateUser([
                    self::KEY_EMAIL => 'member2@example.com',
                ]),
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
        $userService = $this->container->get(UserService::class);

        $users = [
            'public' => $userService->getPublicUser(),
            'private' => $this->createAndActivateUser([
                self::KEY_EMAIL => 'private@example.com',
            ]),
        ];

        return $users;
    }
}
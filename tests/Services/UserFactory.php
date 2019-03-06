<?php

namespace App\Tests\Services;

use App\Entity\User;
use App\Services\Team\Service;
use App\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;

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

    private $entityManager;
    private $userService;
    private $userAccountPlanFactory;
    private $teamService;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserService $userService,
        UserAccountPlanFactory $userAccountPlanFactory,
        Service $teamService
    ) {
        $this->entityManager = $entityManager;
        $this->userService = $userService;
        $this->userAccountPlanFactory = $userAccountPlanFactory;
        $this->teamService = $teamService;
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

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * @param array $userValues
     *
     * @return User
     */
    public function create($userValues = [])
    {
        foreach ($this->defaultUserValues as $key => $value) {
            if (!array_key_exists($key, $userValues)) {
                $userValues[$key] = $value;
            }
        }

        if ($this->userService->exists($userValues[self::KEY_EMAIL])) {
            /* @var User $user */
            $user = $this->userService->findUserByEmail($userValues[self::KEY_EMAIL]);

            return $user;
        }

        $user = $this->userService->create($userValues[self::KEY_EMAIL], 'password');

        if (isset($userValues[self::KEY_PLAN_NAME])) {
            $this->userAccountPlanFactory->create($user, $userValues[self::KEY_PLAN_NAME]);
        }

        return $user;
    }

    /**
     * @return User[]
     */
    public function createPublicPrivateAndTeamUserSet()
    {
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

        $team = $this->teamService->create('Foo', $users['leader']);

        $teamMemberService = $this->teamService->getMemberService();

        $teamMemberService->add($team, $users['member1']);
        $teamMemberService->add($team, $users['member2']);

        return $users;
    }

    /**
     * @return User[]
     */
    public function createPublicAndPrivateUserSet()
    {
        $users = [
            'public' => $this->userService->getPublicUser(),
            'private' => $this->createAndActivateUser([
                self::KEY_EMAIL => 'private@example.com',
            ]),
        ];

        return $users;
    }
}

<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use FOS\UserBundle\Util\UserManipulator;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserService;

class UserFactory
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var UserManipulator
     */
    private $userManipulator;

    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @var AccountPlanService
     */
    private $accountPlanService;

    /**
     * @param UserService $userService
     * @param UserManipulator $userManipulator
     * @param UserAccountPlanService $userAccountPlanService
     * @param AccountPlanService $accountPlanService
     */
    public function __construct(
        UserService $userService,
        UserManipulator $userManipulator,
        UserAccountPlanService $userAccountPlanService,
        AccountPlanService $accountPlanService
    ) {
        $this->userService = $userService;
        $this->userManipulator = $userManipulator;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->accountPlanService = $accountPlanService;
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
    public function create($email, $planName = 'basic')
    {
        if ($this->userService->exists($email)) {
            /* @var User $user */
            $user = $this->userService->findUserByEmail($email);

            return $user;
        }

        $user = $this->userService->create($email, 'password1');
        $this->userAccountPlanService->subscribe(
            $user,
            $this->accountPlanService->find($planName)
        );

        return $user;
    }

    /**
     * @param User $user
     */
    public function activate(User $user)
    {
        $this->userManipulator->activate($user->getEmail());
    }
}

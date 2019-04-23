<?php

namespace App\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\Services\AccountPlanService;
use App\Services\UserAccountPlanService;
use App\Services\UserService;

class SetPublicUserAccountPlan extends Fixture implements DependentFixtureInterface
{
    /**
     * @var UserService
     */
    private $userService;

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
     * @param UserAccountPlanService $userAccountPlanService
     * @param AccountPlanService $accountPlanService
     */
    public function __construct(
        UserService $userService,
        UserAccountPlanService $userAccountPlanService,
        AccountPlanService $accountPlanService
    ) {
        $this->userService = $userService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->accountPlanService = $accountPlanService;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $publicUser = $this->userService->getPublicUser();
        $publicUserAccountPlan = $this->userAccountPlanService->getForUser($publicUser);

        if (empty($publicUserAccountPlan)) {
            $plan = $this->accountPlanService->get('public');
            $this->userAccountPlanService->subscribe($publicUser, $plan);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadUserData::class,
        ];
    }
}

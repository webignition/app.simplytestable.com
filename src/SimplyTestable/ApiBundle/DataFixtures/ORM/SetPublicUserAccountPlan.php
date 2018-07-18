<?php

namespace SimplyTestable\ApiBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserService;

class SetPublicUserAccountPlan extends Fixture implements DependentFixtureInterface
{
    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @var AccountPlanService
     */
    private $accountPlanService;

    /**
     * @var User
     */
    private $publicUser;

    /**
     * @var UserAccountPlan
     */
    private $publicUserAccountPlan;

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
        $this->userAccountPlanService = $userAccountPlanService;
        $this->accountPlanService = $accountPlanService;

        $this->publicUser = $userService->getPublicUser();
        $this->publicUserAccountPlan = $userAccountPlanService->getForUser($this->publicUser);
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        if (empty($this->publicUserAccountPlan)) {
            $plan = $this->accountPlanService->get('public');
            $this->userAccountPlanService->subscribe($this->publicUser, $plan);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadUserData::class,
            LoadAccountPlans::class,
        ];
    }
}

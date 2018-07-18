<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Services\UserAccountPlanService;
use AppBundle\Services\UserService;
use AppBundle\Entity\UserAccountPlan;

class NormaliseUserAccountPlans extends Fixture implements DependentFixtureInterface
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
     * @var int
     */
    private $defaultTrialPeriod;

    /**
     * @param UserService $userService
     * @param UserAccountPlanService $userAccountPlanService
     * @param int $defaultTrialPeriod
     */
    public function __construct(
        UserService $userService,
        UserAccountPlanService $userAccountPlanService,
        $defaultTrialPeriod
    ) {
        $this->userService = $userService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->defaultTrialPeriod = (int)$defaultTrialPeriod;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $userAccountPlanRepository = $manager->getRepository(UserAccountPlan::class);

        /* @var UserAccountPlan[] $userAccountPlans */
        $userAccountPlans = $userAccountPlanRepository->findAll();

        foreach ($userAccountPlans as $userAccountPlan) {
            if ($this->userService->isSpecialUser($userAccountPlan->getUser())) {
                continue;
            }

            /* @var $userAccountPlan UserAccountPlan */
            $isModified = false;

            if (is_null($userAccountPlan->getStartTrialPeriod())) {
                $userAccountPlan->setStartTrialPeriod($this->defaultTrialPeriod);
                $isModified = true;
            }

            if ($isModified === true) {
                $manager->persist($userAccountPlan);
                $manager->flush();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadAccountPlans::class,
            LoadUserData::class,
            SetPublicUserAccountPlan::class,
        ];
    }
}

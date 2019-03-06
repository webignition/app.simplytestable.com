<?php

namespace App\Tests\Services;

use App\Entity\User;
use App\Entity\UserAccountPlan;
use App\Services\AccountPlanService;
use Doctrine\ORM\EntityManagerInterface;

class UserAccountPlanFactory
{
    private $entityManager;
    private $accountPlanService;

    public function __construct(
        EntityManagerInterface $entityManager,
        AccountPlanService $accountPlanService
    ) {
        $this->entityManager = $entityManager;
        $this->accountPlanService = $accountPlanService;
    }

    /**
     * @param User $user
     * @param string $planName
     * @param string $stripeCustomerId
     *
     * @return UserAccountPlan
     */
    public function create(User $user, $planName, $stripeCustomerId = null)
    {
        $plan = $this->accountPlanService->get($planName);

        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setUser($user);
        $userAccountPlan->setPlan($plan);
        $userAccountPlan->setStartTrialPeriod(getenv('DEFAULT_TRIAL_PERIOD'));
        $userAccountPlan->setIsActive(true);

        if ($planName !== 'basic' || !empty($stripeCustomerId)) {
            if (empty($stripeCustomerId)) {
                $stripeCustomerId = md5(rand());
            }

            $userAccountPlan->setStripeCustomer($stripeCustomerId);
        }

        $this->entityManager->persist($userAccountPlan);
        $this->entityManager->flush();

        return $userAccountPlan;
    }
}

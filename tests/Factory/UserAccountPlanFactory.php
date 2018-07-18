<?php

namespace App\Tests\Factory;

use App\Entity\User;
use App\Entity\UserAccountPlan;
use App\Services\AccountPlanService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserAccountPlanFactory
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
     * @param User $user
     * @param string $planName
     * @param string $stripeCustomerId
     *
     * @return UserAccountPlan
     */
    public function create(User $user, $planName, $stripeCustomerId = null)
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $accountPlanService = $this->container->get(AccountPlanService::class);

        $plan = $accountPlanService->get($planName);

        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setUser($user);
        $userAccountPlan->setPlan($plan);
        $userAccountPlan->setStartTrialPeriod($this->container->getParameter('default_trial_period'));
        $userAccountPlan->setIsActive(true);

        if ($planName !== 'basic' || !empty($stripeCustomerId)) {
            if (empty($stripeCustomerId)) {
                $stripeCustomerId = md5(rand());
            }

            $userAccountPlan->setStripeCustomer($stripeCustomerId);
        }

        $entityManager->persist($userAccountPlan);
        $entityManager->flush($userAccountPlan);

        return $userAccountPlan;
    }
}

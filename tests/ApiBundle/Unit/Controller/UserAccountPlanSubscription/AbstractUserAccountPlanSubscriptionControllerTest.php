<?php

namespace Tests\ApiBundle\Unit\Controller\UserAccountPlanSubscription;

use SimplyTestable\ApiBundle\Controller\UserAccountPlanSubscriptionController;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\StripeService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Factory\MockFactory;

abstract class AbstractUserAccountPlanSubscriptionControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $services
     *
     * @return UserAccountPlanSubscriptionController
     */
    protected function createUserAccountPlanSubscriptionController($services = [])
    {
        if (!isset($services[ApplicationStateService::class])) {
            $services[ApplicationStateService::class] = MockFactory::createApplicationStateService();
        }

        if (!isset($services[UserService::class])) {
            $services[UserService::class] = MockFactory::createUserService();
        }

        if (!isset($services[UserAccountPlanService::class])) {
            $services[UserAccountPlanService::class] = MockFactory::createUserAccountPlanService();
        }

        if (!isset($services[AccountPlanService::class])) {
            $services[AccountPlanService::class] = MockFactory::createAccountPlanService();
        }

        if (!isset($services[StripeService::class])) {
            $services[StripeService::class] = MockFactory::createStripeService();
        }

        $teamController = new UserAccountPlanSubscriptionController(
            $services[ApplicationStateService::class],
            $services[UserService::class],
            $services[UserAccountPlanService::class],
            $services[AccountPlanService::class],
            $services[StripeService::class]
        );

        return $teamController;
    }
}

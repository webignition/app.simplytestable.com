<?php

namespace Tests\AppBundle\Unit\Controller\UserAccountPlanSubscription;

use AppBundle\Controller\UserAccountPlanSubscriptionController;
use AppBundle\Services\AccountPlanService;
use AppBundle\Services\ApplicationStateService;
use AppBundle\Services\StripeService;
use AppBundle\Services\UserAccountPlanService;
use AppBundle\Services\UserService;
use Tests\AppBundle\Factory\MockFactory;

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

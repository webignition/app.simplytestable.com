<?php

namespace Tests\ApiBundle\Functional\Controller\UserAccountPlanSubscription;

use SimplyTestable\ApiBundle\Controller\UserAccountPlanSubscriptionController;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

abstract class AbstractUserAccountPlanSubscriptionControllerTest extends AbstractBaseTestCase
{
    /**
     * @var UserAccountPlanSubscriptionController
     */
    protected $userAccountPlanSubscriptionController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userAccountPlanSubscriptionController = $this->container->get(
            UserAccountPlanSubscriptionController::class
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}

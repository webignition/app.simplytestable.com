<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserAccountPlanSubscription;

use SimplyTestable\ApiBundle\Controller\UserAccountPlanSubscriptionController;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;

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

        $this->userAccountPlanSubscriptionController = new UserAccountPlanSubscriptionController();
        $this->userAccountPlanSubscriptionController->setContainer($this->container);
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

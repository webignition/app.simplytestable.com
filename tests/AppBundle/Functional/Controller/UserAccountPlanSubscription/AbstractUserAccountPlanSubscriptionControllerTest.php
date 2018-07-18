<?php

namespace Tests\AppBundle\Functional\Controller\UserAccountPlanSubscription;

use AppBundle\Controller\UserAccountPlanSubscriptionController;
use Tests\AppBundle\Functional\Controller\AbstractControllerTest;

abstract class AbstractUserAccountPlanSubscriptionControllerTest extends AbstractControllerTest
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

        $this->userAccountPlanSubscriptionController = self::$container->get(
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

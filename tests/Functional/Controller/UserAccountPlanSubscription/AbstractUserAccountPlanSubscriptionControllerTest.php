<?php

namespace App\Tests\Functional\Controller\UserAccountPlanSubscription;

use App\Controller\UserAccountPlanSubscriptionController;
use App\Tests\Functional\Controller\AbstractControllerTest;

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

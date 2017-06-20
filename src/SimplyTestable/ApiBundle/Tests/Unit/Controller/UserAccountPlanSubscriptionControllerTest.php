<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Controller;

use SimplyTestable\ApiBundle\Controller\UserAccountPlanSubscriptionController;
use SimplyTestable\ApiBundle\Tests\Factory\ContainerFactory;

class UserAccountPlanSubscriptionControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testSubscribeActionInMaintenanceReadOnlyMode()
    {
        $controller = new UserAccountPlanSubscriptionController();
        $controller->setContainer(
            ContainerFactory::createForMaintenanceReadOnlyModeControllerTest()
        );

        $response = $controller->subscribeAction('user@example.com', 'foo');

        $this->assertEquals(503, $response->getStatusCode());
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}

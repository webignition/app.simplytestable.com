<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Controller;

use SimplyTestable\ApiBundle\Controller\UserCreationController;
use SimplyTestable\ApiBundle\Tests\Factory\ContainerFactory;

class UserCreationControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testActivateActionInMaintenanceReadOnlyMode()
    {
        $controller = new UserCreationController();
        $controller->setContainer(
            ContainerFactory::createForMaintenanceReadOnlyModeControllerTest()
        );

        $response = $controller->activateAction();

        $this->assertEquals(503, $response->getStatusCode());
    }

    public function testCreateActionInMaintenanceReadOnlyMode()
    {
        $controller = new UserCreationController();
        $controller->setContainer(
            ContainerFactory::createForMaintenanceReadOnlyModeControllerTest()
        );

        $response = $controller->createAction();

        $this->assertEquals(503, $response->getStatusCode());
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}

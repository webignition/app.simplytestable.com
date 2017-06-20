<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Controller\Worker;

use SimplyTestable\ApiBundle\Controller\Worker\TasksController;
use SimplyTestable\ApiBundle\Tests\Factory\ContainerFactory;

class TasksControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testRequestActionInMaintenanceReadOnlyMode()
    {
        $controller = new TasksController();
        $controller->setContainer(
            ContainerFactory::createForMaintenanceReadOnlyModeControllerTest()
        );

        $response = $controller->requestAction();

        $this->assertEquals(503, $response->getStatusCode());
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}

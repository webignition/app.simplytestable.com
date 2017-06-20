<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Controller;

use SimplyTestable\ApiBundle\Controller\TaskController;
use SimplyTestable\ApiBundle\Tests\Factory\ContainerFactory;

class TaskControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testCompleteActionInMaintenanceReadOnlyMode()
    {
        $controller = new TaskController();
        $controller->setContainer(
            ContainerFactory::createForMaintenanceReadOnlyModeControllerTest()
        );

        $response = $controller->completeAction();

        $this->assertEquals(503, $response->getStatusCode());
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}

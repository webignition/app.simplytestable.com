<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Controller\Worker;

use SimplyTestable\ApiBundle\Controller\Worker\TasksController;
use SimplyTestable\ApiBundle\Tests\Factory\ContainerFactory;
use SimplyTestable\ApiBundle\Tests\Unit\Controller\MaintenanceStatesDataProviderTrait;

class TasksControllerTest extends \PHPUnit_Framework_TestCase
{
    use MaintenanceStatesDataProviderTrait;

    /**
     * @dataProvider maintenanceStatesDataProvider
     *
     * @param array $maintenanceStates
     */
    public function testRequestActionInMaintenanceReadOnlyMode($maintenanceStates)
    {
        $controller = new TasksController();
        $controller->setContainer(
            ContainerFactory::createForMaintenanceReadOnlyModeControllerTest($maintenanceStates)
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

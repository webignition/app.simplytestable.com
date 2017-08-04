<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Controller\Job;

use SimplyTestable\ApiBundle\Controller\Job\JobController;
use SimplyTestable\ApiBundle\Tests\Factory\ContainerFactory;
use SimplyTestable\ApiBundle\Tests\Unit\Controller\MaintenanceStatesDataProviderTrait;

class JobControllerTest extends \PHPUnit_Framework_TestCase
{
    use MaintenanceStatesDataProviderTrait;

    /**
     * @dataProvider maintenanceStatesDataProvider
     *
     * @param array $maintenanceStates
     */
    public function testCancelActionInMaintenanceReadOnlyMode($maintenanceStates)
    {
        $controller = new JobController();
        $controller->setContainer(
            ContainerFactory::createForMaintenanceReadOnlyModeControllerTest($maintenanceStates)
        );

        $response = $controller->cancelAction('foo', 1);

        $this->assertEquals(503, $response->getStatusCode());
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
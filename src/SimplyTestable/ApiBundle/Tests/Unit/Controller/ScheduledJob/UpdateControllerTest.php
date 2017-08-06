<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\ScheduledJob\UpdateController;
use SimplyTestable\ApiBundle\Tests\Factory\ContainerFactory;
use SimplyTestable\ApiBundle\Tests\Unit\Controller\MaintenanceStatesDataProviderTrait;
use Symfony\Component\HttpFoundation\Request;

class UpdateControllerTest extends \PHPUnit_Framework_TestCase
{
    use MaintenanceStatesDataProviderTrait;

    /**
     * @dataProvider maintenanceStatesDataProvider
     *
     * @param array $maintenanceStates
     */
    public function testActivateActionInMaintenanceReadOnlyMode($maintenanceStates)
    {
        $controller = new UpdateController();
        $controller->setContainer(
            ContainerFactory::createForMaintenanceReadOnlyModeControllerTest($maintenanceStates)
        );

        $response = $controller->updateAction(new Request(), 1);

        $this->assertEquals(503, $response->getStatusCode());
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}

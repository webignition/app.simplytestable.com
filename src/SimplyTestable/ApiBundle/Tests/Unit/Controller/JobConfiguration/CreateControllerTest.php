<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\JobConfiguration\CreateController;
use SimplyTestable\ApiBundle\Tests\Factory\ContainerFactory;
use SimplyTestable\ApiBundle\Tests\Unit\Controller\MaintenanceStatesDataProviderTrait;
use Symfony\Component\HttpFoundation\Request;

class CreateControllerTest extends \PHPUnit_Framework_TestCase
{
    use MaintenanceStatesDataProviderTrait;

    /**
     * @dataProvider maintenanceStatesDataProvider
     *
     * @param array $maintenanceStates
     */
    public function testActivateActionInMaintenanceReadOnlyMode($maintenanceStates)
    {
        $controller = new CreateController();
        $controller->setContainer(
            ContainerFactory::createForMaintenanceReadOnlyModeControllerTest($maintenanceStates)
        );

        $response = $controller->createAction(new Request());

        $this->assertEquals(503, $response->getStatusCode());
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}

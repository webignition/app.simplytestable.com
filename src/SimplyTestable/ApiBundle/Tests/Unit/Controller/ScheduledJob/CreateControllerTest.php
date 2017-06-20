<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\ScheduledJob\CreateController;
use SimplyTestable\ApiBundle\Tests\Factory\ContainerFactory;
use Symfony\Component\HttpFoundation\Request;

class CreateControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testActivateActionInMaintenanceReadOnlyMode()
    {
        $controller = new CreateController();
        $controller->setContainer(
            ContainerFactory::createForMaintenanceReadOnlyModeControllerTest()
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

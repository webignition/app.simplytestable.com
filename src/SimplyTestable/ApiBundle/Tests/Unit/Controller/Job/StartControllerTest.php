<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Controller\Job;

use SimplyTestable\ApiBundle\Controller\Job\StartController;
use SimplyTestable\ApiBundle\Tests\Factory\ContainerFactory;
use Symfony\Component\HttpFoundation\Request;

class StartControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testActivateActionInMaintenanceReadOnlyMode()
    {
        $controller = new StartController();
        $controller->setContainer(
            ContainerFactory::createForMaintenanceReadOnlyModeControllerTest()
        );

        $response = $controller->startAction(new Request(), 'foo');

        $this->assertEquals(503, $response->getStatusCode());
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}

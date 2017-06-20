<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\JobConfiguration\UpdateController;
use SimplyTestable\ApiBundle\Tests\Factory\ContainerFactory;
use Symfony\Component\HttpFoundation\Request;

class UpdateControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testActivateActionInMaintenanceReadOnlyMode()
    {
        $controller = new UpdateController();
        $controller->setContainer(
            ContainerFactory::createForMaintenanceReadOnlyModeControllerTest()
        );

        $response = $controller->updateAction(new Request(), 'foo');

        $this->assertEquals(503, $response->getStatusCode());
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}

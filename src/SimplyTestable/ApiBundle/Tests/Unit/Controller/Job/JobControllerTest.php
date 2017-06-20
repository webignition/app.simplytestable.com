<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Controller\Job;

use SimplyTestable\ApiBundle\Controller\Job\JobController;
use SimplyTestable\ApiBundle\Tests\Factory\ContainerFactory;

class JobControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testActivateActionInMaintenanceReadOnlyMode()
    {
        $controller = new JobController();
        $controller->setContainer(
            ContainerFactory::createForMaintenanceReadOnlyModeControllerTest()
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

<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Controller;

use SimplyTestable\ApiBundle\Controller\UserPasswordResetController;
use SimplyTestable\ApiBundle\Tests\Factory\ContainerFactory;

class UserPasswordResetControllerTest extends \PHPUnit_Framework_TestCase
{
    use MaintenanceStatesDataProviderTrait;

    /**
     * @dataProvider maintenanceStatesDataProvider
     *
     * @param array $maintenanceStates
     */
    public function testResetPasswordActionInMaintenanceReadOnlyMode($maintenanceStates)
    {
        $controller = new UserPasswordResetController();
        $controller->setContainer(
            ContainerFactory::createForMaintenanceReadOnlyModeControllerTest($maintenanceStates)
        );

        $response = $controller->resetPasswordAction('foo');

        $this->assertEquals(503, $response->getStatusCode());
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}

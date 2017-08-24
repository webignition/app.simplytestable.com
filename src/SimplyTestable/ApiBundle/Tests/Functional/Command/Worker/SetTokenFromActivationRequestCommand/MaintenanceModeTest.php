<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Worker\SetTokenFromActivationRequestCommand;

use SimplyTestable\ApiBundle\Controller\MaintenanceController;

class MaintenanceModeTest extends CommandTest {

    public function testReturnCode() {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);
        $maintenanceController->enableReadOnlyAction();

        $this->assertEquals(1, $this->executeCommand($this->getCommandName()));

        $maintenanceController->disableReadOnlyAction();
    }

}
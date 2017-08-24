<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Cancel\Collection;

use SimplyTestable\ApiBundle\Controller\MaintenanceController;

class CancelCollectionCommandTest extends BaseTest {

    public function testCancelInMaintenanceReadOnlyModeReturnsStatusCode1() {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);
        $maintenanceController->enableReadOnlyAction();

        $this->assertReturnCode(1, array(
            'ids' => implode(',', array(1,2,3))
        ));

        $maintenanceController->disableReadOnlyAction();
    }

}

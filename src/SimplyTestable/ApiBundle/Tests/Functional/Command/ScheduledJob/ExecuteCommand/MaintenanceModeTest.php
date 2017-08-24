<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\ScheduledJob\ExecuteCommand;

use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;

class MaintenanceModeTest extends WithoutScheduledJobTest {

    protected function preCall() {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);
        $maintenanceController->enableReadOnlyAction();
    }

    protected function getExpectedReturnCode() {
        return ExecuteCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
    }


    protected function getScheduledJobId()
    {
        return 1;
    }


    public function testResqueExecuteJobIsEnqueued() {
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $this->assertTrue($resqueQueueService->contains('scheduledjob-execute', [
            'id' => $this->getScheduledJobId()
        ]));
    }

    protected function tearDown()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);
        $maintenanceController->disableReadOnlyAction();
    }
}

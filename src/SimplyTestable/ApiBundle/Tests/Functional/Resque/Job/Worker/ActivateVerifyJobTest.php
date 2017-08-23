<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\Worker;

use SimplyTestable\ApiBundle\Command\WorkerActivateVerifyCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Resque\Job\Worker\ActivateVerifyJob;
use SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\AbstractJobTest;

class ActivateVerifyJobTest extends AbstractJobTest
{
    const QUEUE = 'worker-activate-verify';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);

        $maintenanceController->enableReadOnlyAction();

        $job = $this->createJob(['id' => 1], self::QUEUE);
        $this->assertInstanceOf(ActivateVerifyJob::class, $job);

        $returnCode = $job->run([]);

        $maintenanceController->disableReadOnlyAction();

        $this->assertEquals(WorkerActivateVerifyCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }
}

<?php

namespace Tests\ApiBundle\Functional\Resque\Job\Worker;

use SimplyTestable\ApiBundle\Command\Worker\ActivateVerifyCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Resque\Job\Worker\ActivateVerifyJob;
use Tests\ApiBundle\Functional\Resque\Job\AbstractJobTest;

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

        $this->assertEquals(ActivateVerifyCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }
}
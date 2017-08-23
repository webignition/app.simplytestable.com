<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\Task;

use SimplyTestable\ApiBundle\Command\Task\Cancel\Command;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Resque\Job\Task\CancelJob;
use SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\AbstractJobTest;

class CancelJobTest extends AbstractJobTest
{
    const QUEUE = 'task-cancel';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);

        $maintenanceController->enableReadOnlyAction();

        $job = $this->createJob(['id' => 1], self::QUEUE);
        $this->assertInstanceOf(CancelJob::class, $job);

        $returnCode = $job->run([]);

        $maintenanceController->disableReadOnlyAction();

        $this->assertEquals(Command::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }
}

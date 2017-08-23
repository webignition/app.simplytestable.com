<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\Job;

use SimplyTestable\ApiBundle\Command\Job\PrepareCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Resque\Job\Job\PrepareJob;
use SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\AbstractJobTest;

class PrepareJobTest extends AbstractJobTest
{
    const QUEUE = 'job-prepare';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);

        $maintenanceController->enableReadOnlyAction();

        $job = $this->createJob(['id' => 1], self::QUEUE);
        $this->assertInstanceOf(PrepareJob::class, $job);

        $returnCode = $job->run([]);

        $maintenanceController->disableReadOnlyAction();

        $this->assertEquals(PrepareCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }
}

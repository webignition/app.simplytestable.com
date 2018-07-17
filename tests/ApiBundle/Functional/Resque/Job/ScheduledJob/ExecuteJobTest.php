<?php

namespace Tests\ApiBundle\Functional\Resque\Job\ScheduledJob;

use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;
use SimplyTestable\ApiBundle\Resque\Job\ScheduledJob\ExecuteJob;
use Tests\ApiBundle\Functional\Resque\Job\AbstractJobTest;

class ExecuteJobTest extends AbstractJobTest
{
    const QUEUE = 'scheduledjob-execute';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $job = new ExecuteJob(['id' => 1]);
        $this->initialiseJob($job, self::$container->get(ExecuteCommand::class));

        $this->assertEquals(
            ExecuteCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $this->runInMaintenanceReadOnlyMode($job)
        );
    }
}

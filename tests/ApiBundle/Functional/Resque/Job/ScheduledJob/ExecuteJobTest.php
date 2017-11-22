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
        $job = $this->createJob(['id' => 1], self::QUEUE);
        $this->assertInstanceOf(ExecuteJob::class, $job);

        $returnCode = $this->runInMaintenanceReadOnlyMode($job);

        $this->assertEquals(ExecuteCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }
}

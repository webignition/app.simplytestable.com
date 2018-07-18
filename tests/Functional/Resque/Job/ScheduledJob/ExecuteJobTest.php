<?php

namespace App\Tests\Functional\Resque\Job\ScheduledJob;

use App\Command\ScheduledJob\ExecuteCommand;
use App\Resque\Job\ScheduledJob\ExecuteJob;
use App\Tests\Functional\Resque\Job\AbstractJobTest;

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

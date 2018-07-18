<?php

namespace App\Tests\Functional\Resque\Job\Job;

use App\Command\Job\PrepareCommand;
use App\Resque\Job\Job\PrepareJob;
use App\Tests\Functional\Resque\Job\AbstractJobTest;

class PrepareJobTest extends AbstractJobTest
{
    const QUEUE = 'job-prepare';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $job = new PrepareJob(['id' => 1]);
        $this->initialiseJob($job, self::$container->get(PrepareCommand::class));

        $this->assertEquals(
            PrepareCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $this->runInMaintenanceReadOnlyMode($job)
        );
    }
}

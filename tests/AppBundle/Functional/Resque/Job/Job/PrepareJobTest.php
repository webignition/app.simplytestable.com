<?php

namespace Tests\AppBundle\Functional\Resque\Job\Job;

use AppBundle\Command\Job\PrepareCommand;
use AppBundle\Resque\Job\Job\PrepareJob;
use Tests\AppBundle\Functional\Resque\Job\AbstractJobTest;

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

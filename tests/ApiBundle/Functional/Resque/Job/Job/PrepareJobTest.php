<?php

namespace Tests\ApiBundle\Functional\Resque\Job\Job;

use SimplyTestable\ApiBundle\Command\Job\PrepareCommand;
use SimplyTestable\ApiBundle\Resque\Job\Job\PrepareJob;
use Tests\ApiBundle\Functional\Resque\Job\AbstractJobTest;

class PrepareJobTest extends AbstractJobTest
{
    const QUEUE = 'job-prepare';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $job = $this->createJob(['id' => 1], self::QUEUE);
        $this->assertInstanceOf(PrepareJob::class, $job);

        $returnCode = $this->runInMaintenanceReadOnlyMode($job);

        $this->assertEquals(PrepareCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }
}

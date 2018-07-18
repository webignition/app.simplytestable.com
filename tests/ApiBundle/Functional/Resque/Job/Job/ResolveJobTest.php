<?php

namespace Tests\ApiBundle\Functional\Resque\Job\Job;

use SimplyTestable\ApiBundle\Command\Job\ResolveWebsiteCommand;
use SimplyTestable\ApiBundle\Resque\Job\Job\ResolveJob;
use Tests\ApiBundle\Functional\Resque\Job\AbstractJobTest;

class ResolveJobTest extends AbstractJobTest
{
    const QUEUE = 'job-resolve';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $job = new ResolveJob(['id' => 1]);
        $this->initialiseJob($job, self::$container->get(ResolveWebsiteCommand::class));

        $this->assertEquals(
            ResolveWebsiteCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $this->runInMaintenanceReadOnlyMode($job)
        );
    }
}

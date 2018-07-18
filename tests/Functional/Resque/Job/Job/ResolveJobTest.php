<?php

namespace App\Tests\Functional\Resque\Job\Job;

use App\Command\Job\ResolveWebsiteCommand;
use App\Resque\Job\Job\ResolveJob;
use App\Tests\Functional\Resque\Job\AbstractJobTest;

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

<?php

namespace Tests\ApiBundle\Functional\Resque\Job\Worker;

use SimplyTestable\ApiBundle\Command\Worker\ActivateVerifyCommand;
use SimplyTestable\ApiBundle\Resque\Job\Worker\ActivateVerifyJob;
use Tests\ApiBundle\Functional\Resque\Job\AbstractJobTest;

class ActivateVerifyJobTest extends AbstractJobTest
{
    const QUEUE = 'worker-activate-verify';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $job = $this->createJob(
            ['id' => 1],
            self::QUEUE,
            $this->container->get(ActivateVerifyCommand::class)
        );
        $this->assertInstanceOf(ActivateVerifyJob::class, $job);

        $returnCode = $this->runInMaintenanceReadOnlyMode($job);

        $this->assertEquals(ActivateVerifyCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }
}

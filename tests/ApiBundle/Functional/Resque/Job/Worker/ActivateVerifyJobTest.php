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
        $job = new ActivateVerifyJob(['id' => 1]);
        $this->initialiseJob($job, self::$container->get(ActivateVerifyCommand::class));

        $this->assertEquals(
            ActivateVerifyCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $this->runInMaintenanceReadOnlyMode($job)
        );
    }
}

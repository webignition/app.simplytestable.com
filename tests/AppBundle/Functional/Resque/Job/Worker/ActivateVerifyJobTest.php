<?php

namespace Tests\AppBundle\Functional\Resque\Job\Worker;

use AppBundle\Command\Worker\ActivateVerifyCommand;
use AppBundle\Resque\Job\Worker\ActivateVerifyJob;
use Tests\AppBundle\Functional\Resque\Job\AbstractJobTest;

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

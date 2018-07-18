<?php

namespace App\Tests\Functional\Resque\Job\Worker;

use App\Command\Worker\ActivateVerifyCommand;
use App\Resque\Job\Worker\ActivateVerifyJob;
use App\Tests\Functional\Resque\Job\AbstractJobTest;

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

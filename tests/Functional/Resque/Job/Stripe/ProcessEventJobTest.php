<?php

namespace App\Tests\Functional\Resque\Job\Stripe;

use App\Command\Stripe\Event\ProcessCommand;
use App\Resque\Job\Stripe\ProcessEventJob;
use App\Tests\Functional\Resque\Job\AbstractJobTest;

class ProcessEventJobTest extends AbstractJobTest
{
    const QUEUE = 'stripe-event';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $job = new ProcessEventJob(['stripeId' => 'evt_2c6KUnrLeIFqQv']);
        $this->initialiseJob($job, self::$container->get(ProcessCommand::class));

        $this->assertEquals(
            ProcessCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $this->runInMaintenanceReadOnlyMode($job)
        );
    }
}

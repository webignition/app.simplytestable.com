<?php

namespace Tests\AppBundle\Functional\Resque\Job\Stripe;

use AppBundle\Command\Stripe\Event\ProcessCommand;
use AppBundle\Resque\Job\Stripe\ProcessEventJob;
use Tests\AppBundle\Functional\Resque\Job\AbstractJobTest;

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

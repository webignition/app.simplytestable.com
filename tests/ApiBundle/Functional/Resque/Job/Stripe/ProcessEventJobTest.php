<?php

namespace Tests\ApiBundle\Functional\Resque\Job\Stripe;

use SimplyTestable\ApiBundle\Command\Stripe\Event\ProcessCommand;
use SimplyTestable\ApiBundle\Resque\Job\Stripe\ProcessEventJob;
use Tests\ApiBundle\Functional\Resque\Job\AbstractJobTest;

class ProcessEventJobTest extends AbstractJobTest
{
    const QUEUE = 'stripe-event';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $job = new ProcessEventJob(['stripeId' => 'evt_2c6KUnrLeIFqQv']);
        $this->initialiseJob($job, $this->container->get(ProcessCommand::class));

        $this->assertEquals(
            ProcessCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $this->runInMaintenanceReadOnlyMode($job)
        );
    }
}

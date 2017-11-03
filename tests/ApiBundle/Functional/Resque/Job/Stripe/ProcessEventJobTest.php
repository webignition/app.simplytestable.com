<?php

namespace Tests\ApiBundle\Functional\Resque\Job\Stripe;

use SimplyTestable\ApiBundle\Command\Stripe\Event\ProcessCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Resque\Job\Stripe\ProcessEventJob;
use Tests\ApiBundle\Functional\Resque\Job\AbstractJobTest;

class ProcessEventJobTest extends AbstractJobTest
{
    const QUEUE = 'stripe-event';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);

        $maintenanceController->enableReadOnlyAction();

        $job = $this->createJob(['stripeId' => 'evt_2c6KUnrLeIFqQv'], self::QUEUE);
        $this->assertInstanceOf(ProcessEventJob::class, $job);

        $returnCode = $job->run([]);

        $maintenanceController->disableReadOnlyAction();

        $this->assertEquals(ProcessCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }
}

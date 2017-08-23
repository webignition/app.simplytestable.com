<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\Stripe;

use SimplyTestable\ApiBundle\Command\Stripe\Event\ProcessCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Resque\Job\Stripe\ProcessEventJob;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class ProcessEventJobTest extends BaseSimplyTestableTestCase
{
    const QUEUE = 'stripe-event';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);

        $maintenanceController->enableReadOnlyAction();

        $job = $this->createJob('evt_2c6KUnrLeIFqQv');

        $returnCode = $job->run([]);

        $maintenanceController->disableReadOnlyAction();

        $this->assertEquals(ProcessCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }

    /**
     * @param string $stripeId
     *
     * @return ProcessEventJob
     */
    private function createJob($stripeId)
    {
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');

        $job = $resqueJobFactory->create(
            self::QUEUE,
            [
                'stripeId' =>  $stripeId,
            ]
        );

        $job->setKernelOptions([
            'kernel.root_dir' => $this->container->getParameter('kernel.root_dir'),
            'kernel.environment' => $this->container->getParameter('kernel.environment'),
        ]);

        return $job;
    }
}

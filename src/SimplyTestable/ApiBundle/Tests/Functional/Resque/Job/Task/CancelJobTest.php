<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\Task;

use SimplyTestable\ApiBundle\Command\Task\Cancel\Command;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Resque\Job\Task\CancelJob;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class CancelJobTest extends BaseSimplyTestableTestCase
{
    const QUEUE = 'task-cancel';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);

        $maintenanceController->enableReadOnlyAction();

        $job = $this->createJob(1);

        $returnCode = $job->run([]);

        $maintenanceController->disableReadOnlyAction();

        $this->assertEquals(Command::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }

    /**
     * @param int $jobId
     *
     * @return CancelJob
     */
    private function createJob($jobId)
    {
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');

        $job = $resqueJobFactory->create(
            self::QUEUE,
            [
                'id' =>  $jobId,
            ]
        );

        $job->setKernelOptions([
            'kernel.root_dir' => $this->container->getParameter('kernel.root_dir'),
            'kernel.environment' => $this->container->getParameter('kernel.environment'),
        ]);

        return $job;
    }
}

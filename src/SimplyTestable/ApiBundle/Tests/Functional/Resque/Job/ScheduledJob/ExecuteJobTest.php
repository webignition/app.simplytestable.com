<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\ScheduledJob;

use SimplyTestable\ApiBundle\Command\Job\PrepareCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Resque\Job\ScheduledJob\ExecuteJob;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class ExecuteJobTest extends BaseSimplyTestableTestCase
{
    const QUEUE = 'scheduledjob-execute';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);

        $maintenanceController->enableReadOnlyAction();

        $jobPrepareJob = $this->createExecuteJob(1);

        $returnCode = $jobPrepareJob->run([]);

        $maintenanceController->disableReadOnlyAction();

        $this->assertEquals(PrepareCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }

    /**
     * @param int $jobId
     *
     * @return ExecuteJob
     */
    private function createExecuteJob($jobId)
    {
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');

        $jobPrepareJob = $resqueJobFactory->create(
            self::QUEUE,
            [
                'id' =>  $jobId,
            ]
        );

        $jobPrepareJob->setKernelOptions([
            'kernel.root_dir' => $this->container->getParameter('kernel.root_dir'),
            'kernel.environment' => $this->container->getParameter('kernel.environment'),
        ]);

        return $jobPrepareJob;
    }
}

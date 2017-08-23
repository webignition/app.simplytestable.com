<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\Job;

use SimplyTestable\ApiBundle\Command\Job\ResolveWebsiteCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Resque\Job\Job\ResolveJob;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class ResolveJobTest extends BaseSimplyTestableTestCase
{
    const QUEUE = 'job-resolve';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);

        $maintenanceController->enableReadOnlyAction();

        $job = $this->createJob(1);

        $returnCode = $job->run([]);

        $maintenanceController->disableReadOnlyAction();

        $this->assertEquals(ResolveWebsiteCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }

    /**
     * @param int $jobId
     *
     * @return ResolveJob
     */
    private function createJob($jobId)
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

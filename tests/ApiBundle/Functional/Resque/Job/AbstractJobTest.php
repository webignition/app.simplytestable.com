<?php

namespace Tests\ApiBundle\Functional\Resque\Job;

use SimplyTestable\ApiBundle\Model\ApplicationStateInterface;
use SimplyTestable\ApiBundle\Resque\Job\Job;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Symfony\Component\Console\Command\Command;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

abstract class AbstractJobTest extends AbstractBaseTestCase
{
    /**
     * @param Job $job
     * @param Command $command
     */
    protected function initialiseJob(Job $job, Command $command)
    {
        $job->setKernelOptions([
            'kernel.root_dir' => $this->container->getParameter('kernel.root_dir'),
            'kernel.environment' => $this->container->getParameter('kernel.environment'),
            'command' => $command,
        ]);
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function runInMaintenanceReadOnlyMode(Job $job)
    {
        $applicationStateService = $this->container->get(ApplicationStateService::class);
        $applicationStateService->setState(ApplicationStateInterface::STATE_MAINTENANCE_READ_ONLY);

        $returnCode = $job->run([]);

        $applicationStateService->setState(ApplicationStateInterface::STATE_ACTIVE);

        return $returnCode;
    }
}

<?php

namespace Tests\ApiBundle\Functional\Resque\Job;

use SimplyTestable\ApiBundle\Resque\Job\Job;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use Symfony\Component\Console\Command\Command;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

abstract class AbstractJobTest extends AbstractBaseTestCase
{
    /**
     * @param array $args
     * @param string $queue
     *
     * @return Job
     */
    public function createJob($args, $queue, Command $command)
    {
        $resqueJobFactory = $this->container->get(ResqueJobFactory::class);

        $job = $resqueJobFactory->create($queue, $args);

        $job->setKernelOptions([
            'kernel.root_dir' => $this->container->getParameter('kernel.root_dir'),
            'kernel.environment' => $this->container->getParameter('kernel.environment'),
            'command' => $command,
        ]);

        return $job;
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function runInMaintenanceReadOnlyMode(Job $job)
    {
        $applicationStateService = $this->container->get(ApplicationStateService::class);
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $returnCode = $job->run([]);

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);

        return $returnCode;
    }
}

<?php

namespace App\Tests\Functional\Resque\Job;

use App\Model\ApplicationStateInterface;
use App\Resque\Job\Job;
use App\Services\ApplicationStateService;
use Symfony\Component\Console\Command\Command;
use App\Tests\Functional\AbstractBaseTestCase;

abstract class AbstractJobTest extends AbstractBaseTestCase
{
    /**
     * @param Job $job
     * @param Command $command
     */
    protected function initialiseJob(Job $job, Command $command)
    {
        $job->setKernelOptions([
            'kernel.root_dir' => self::$container->getParameter('kernel.root_dir'),
            'kernel.environment' => self::$container->getParameter('kernel.environment'),
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
        $applicationStateService = self::$container->get(ApplicationStateService::class);
        $applicationStateService->setState(ApplicationStateInterface::STATE_MAINTENANCE_READ_ONLY);

        $returnCode = $job->run([]);

        $applicationStateService->setState(ApplicationStateInterface::STATE_ACTIVE);

        return $returnCode;
    }
}

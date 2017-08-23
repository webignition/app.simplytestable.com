<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\Task;

use SimplyTestable\ApiBundle\Command\Task\Cancel\CollectionCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Resque\Job\Task\CancelCollectionJob;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class CancelCollectionJobTest extends BaseSimplyTestableTestCase
{
    const QUEUE = 'task-cancel-collection';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);

        $maintenanceController->enableReadOnlyAction();

        $job = $this->createJob('1,2,3');

        $returnCode = $job->run([]);

        $maintenanceController->disableReadOnlyAction();

        $this->assertEquals(CollectionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }

    /**
     * @param string $ids
     *
     * @return CancelCollectionJob
     */
    private function createJob($ids)
    {
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');

        $job = $resqueJobFactory->create(
            self::QUEUE,
            [
                'ids' =>  $ids,
            ]
        );

        $job->setKernelOptions([
            'kernel.root_dir' => $this->container->getParameter('kernel.root_dir'),
            'kernel.environment' => $this->container->getParameter('kernel.environment'),
        ]);

        return $job;
    }
}

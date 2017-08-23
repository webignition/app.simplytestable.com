<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\Task;

use SimplyTestable\ApiBundle\Command\Task\Assign\CollectionCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Resque\Job\Task\AssignCollectionJob;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class AssignCollectionJobTest extends BaseSimplyTestableTestCase
{
    const QUEUE = 'task-assign-collection';

    /**
     * @dataProvider runInMaintenanceReadOnlyModeDataProvider
     *
     * @param array $args
     */
    public function testRunInMaintenanceReadOnlyMode($args)
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);

        $maintenanceController->enableReadOnlyAction();

        $job = $this->createJob($args);

        $returnCode = $job->run([]);

        $maintenanceController->disableReadOnlyAction();

        $this->assertEquals(CollectionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }

    /**
     * @return array
     */
    public function runInMaintenanceReadOnlyModeDataProvider()
    {
        return [
            'without worker' => [
                'args' => [
                    'ids' => '1,2,3',
                ],
            ],
            'with worker' => [
                'args' => [
                    'ids' => '1,2,3',
                    'worker' => 'foo',
                ],
            ],
        ];
    }

    /**
     * @param array $args
     *
     * @return AssignCollectionJob
     */
    private function createJob($args)
    {
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');

        $job = $resqueJobFactory->create(
            self::QUEUE,
            $args
        );

        $job->setKernelOptions([
            'kernel.root_dir' => $this->container->getParameter('kernel.root_dir'),
            'kernel.environment' => $this->container->getParameter('kernel.environment'),
        ]);

        return $job;
    }
}

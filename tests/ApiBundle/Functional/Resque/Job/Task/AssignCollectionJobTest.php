<?php

namespace Tests\ApiBundle\Functional\Resque\Job\Task;

use SimplyTestable\ApiBundle\Command\Task\Assign\CollectionCommand;
use SimplyTestable\ApiBundle\Resque\Job\Task\AssignCollectionJob;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Tests\ApiBundle\Functional\Resque\Job\AbstractJobTest;

class AssignCollectionJobTest extends AbstractJobTest
{
    const QUEUE = 'task-assign-collection';

    /**
     * @dataProvider runInMaintenanceReadOnlyModeDataProvider
     *
     * @param array $args
     */
    public function testRunInMaintenanceReadOnlyMode($args)
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $job = $this->createJob($args, self::QUEUE);
        $this->assertInstanceOf(AssignCollectionJob::class, $job);

        $returnCode = $job->run([]);

        $this->assertEquals(CollectionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
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
}

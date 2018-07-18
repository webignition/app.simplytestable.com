<?php

namespace App\Tests\Functional\Resque\Job\Task;

use App\Command\Task\Assign\CollectionCommand;
use App\Resque\Job\Task\AssignCollectionJob;
use App\Tests\Functional\Resque\Job\AbstractJobTest;

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
        $job = new AssignCollectionJob($args);
        $this->initialiseJob($job, self::$container->get(CollectionCommand::class));

        $this->assertEquals(
            CollectionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $this->runInMaintenanceReadOnlyMode($job)
        );
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

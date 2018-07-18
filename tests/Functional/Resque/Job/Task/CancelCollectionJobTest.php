<?php

namespace App\Tests\Functional\Resque\Job\Task;

use App\Command\Task\Cancel\CollectionCommand;
use App\Resque\Job\Task\CancelCollectionJob;
use App\Tests\Functional\Resque\Job\AbstractJobTest;

class CancelCollectionJobTest extends AbstractJobTest
{
    const QUEUE = 'task-cancel-collection';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $job = new CancelCollectionJob(['ids' => '1,2,3']);
        $this->initialiseJob($job, self::$container->get(CollectionCommand::class));

        $this->assertEquals(
            CollectionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $this->runInMaintenanceReadOnlyMode($job)
        );
    }
}

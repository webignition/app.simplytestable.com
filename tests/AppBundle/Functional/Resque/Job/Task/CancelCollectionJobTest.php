<?php

namespace Tests\AppBundle\Functional\Resque\Job\Task;

use AppBundle\Command\Task\Cancel\CollectionCommand;
use AppBundle\Resque\Job\Task\CancelCollectionJob;
use Tests\AppBundle\Functional\Resque\Job\AbstractJobTest;

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

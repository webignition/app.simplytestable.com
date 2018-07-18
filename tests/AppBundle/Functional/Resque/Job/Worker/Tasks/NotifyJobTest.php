<?php

namespace Tests\AppBundle\Functional\Resque\Job\Worker\Tasks;

use AppBundle\Command\Worker\TaskNotificationCommand;
use AppBundle\Resque\Job\Worker\Tasks\NotifyJob;
use Tests\AppBundle\Functional\Resque\Job\AbstractJobTest;

class NotifyJobTest extends AbstractJobTest
{
    const QUEUE = 'tasks-notify';

    public function testRun()
    {
        $job = new NotifyJob();
        $this->initialiseJob($job, self::$container->get(TaskNotificationCommand::class));

        $this->assertEquals(true, $job->run([]));
    }
}

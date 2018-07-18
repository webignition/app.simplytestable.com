<?php

namespace App\Tests\Functional\Resque\Job\Worker\Tasks;

use App\Command\Worker\TaskNotificationCommand;
use App\Resque\Job\Worker\Tasks\NotifyJob;
use App\Tests\Functional\Resque\Job\AbstractJobTest;

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

<?php

namespace Tests\ApiBundle\Functional\Resque\Job\Worker\Tasks;

use SimplyTestable\ApiBundle\Command\Worker\TaskNotificationCommand;
use SimplyTestable\ApiBundle\Resque\Job\Worker\Tasks\NotifyJob;
use Tests\ApiBundle\Functional\Resque\Job\AbstractJobTest;

class NotifyJobTest extends AbstractJobTest
{
    const QUEUE = 'tasks-notify';

    public function testRun()
    {
        $job = new NotifyJob();
        $this->initialiseJob($job, $this->container->get(TaskNotificationCommand::class));

        $this->assertEquals(true, $job->run([]));
    }
}

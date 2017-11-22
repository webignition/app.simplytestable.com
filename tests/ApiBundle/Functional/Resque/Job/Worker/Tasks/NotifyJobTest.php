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
        $job = $this->createJob(
            [],
            self::QUEUE,
            $this->container->get(TaskNotificationCommand::class)
        );
        $this->assertInstanceOf(NotifyJob::class, $job);

        $returnCode = $job->run([]);

        $this->assertEquals(true, $returnCode);
    }
}

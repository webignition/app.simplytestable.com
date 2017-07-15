<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task;

use SimplyTestable\ApiBundle\Command\Task\EnqueueCancellationForAwaitingCancellationCommand;
use SimplyTestable\ApiBundle\Tests\Functional\ConsoleCommandTestCase;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class EnqueueCancellationForAwaitingCancellationCommandTest extends ConsoleCommandTestCase
{
    /**
     * @return string
     */
    protected function getCommandName()
    {
        return 'simplytestable:task:enqueue-cancellation-for-awaiting-cancellation';
    }

    /**
     * @return ContainerAwareCommand[]
     */
    protected function getAdditionalCommands()
    {
        return array(
            new EnqueueCancellationForAwaitingCancellationCommand()
        );
    }

    public function testCancellationJobsAreEnqueued()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->createJobFactory()->createResolveAndPrepare();

        foreach ($job->getTasks() as $task) {
            $task->setState($this->getTaskService()->getInProgressState());
            $this->getTaskService()->getManager()->persist($task);
        }

        $this->getTaskService()->getManager()->flush();
        $this->getJobService()->getManager()->refresh($job);
        $this->cancelJob($job);

        $this->assertReturnCode(0);
        $this->assertTrue($this->getResqueQueueService()->contains(
            'task-cancel-collection',
            array(
                'ids' => implode(',', $this->getTaskIds($job))
            )
        ));
    }

    public function testExecuteInMaintenanceReadOnlyModeReturnsStatusCode1()
    {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertReturnCode(1);
    }
}
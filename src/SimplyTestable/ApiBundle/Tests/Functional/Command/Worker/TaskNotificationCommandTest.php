<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Worker;

use Mockery\MockInterface;
use SimplyTestable\ApiBundle\Command\Worker\TaskNotificationCommand;
use SimplyTestable\ApiBundle\Services\Worker\TaskNotificationService;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class TaskNotificationCommandTest extends BaseSimplyTestableTestCase
{
    public function testRun()
    {
        /* @var MockInterface|TaskNotificationService $workerTaskNotificationService */
        $workerTaskNotificationService = \Mockery::mock(TaskNotificationService::class);
        $workerTaskNotificationService
            ->shouldReceive('notify')
            ->once()
            ->withNoArgs();

        $command = new TaskNotificationCommand($workerTaskNotificationService);
        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(TaskNotificationCommand::RETURN_CODE_OK, $returnCode);

        \Mockery::close();
    }
}

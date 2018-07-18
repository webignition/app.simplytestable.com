<?php

namespace App\Tests\Functional\Command\Worker;

use Mockery\Mock;
use App\Command\Worker\TaskNotificationCommand;
use App\Services\Worker\TaskNotificationService;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class TaskNotificationCommandTest extends AbstractBaseTestCase
{
    public function testRun()
    {
        /* @var Mock|TaskNotificationService $workerTaskNotificationService */
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

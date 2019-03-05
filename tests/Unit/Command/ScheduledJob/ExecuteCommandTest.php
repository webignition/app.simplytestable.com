<?php

namespace App\Tests\Unit\Command\ScheduledJob\ExecuteCommand;

use App\Repository\ScheduledJobRepository;
use App\Resque\Job\ScheduledJob\ExecuteJob;
use App\Tests\Factory\MockFactory;
use App\Command\ScheduledJob\ExecuteCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ExecuteCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunInMaintenanceReadOnlyMode()
    {
        $scheduledJobId = 1;

        $resqueExecuteJob = \Mockery::mock(ExecuteJob::class);

        $resqueQueueService = MockFactory::createResqueQueueService([
            'contains' => [
                'withArgs' => [
                    'scheduledjob-execute',
                    ['id' => $scheduledJobId]
                ],
                'return' => false,
            ],
            'enqueue' => [
                'with' => $resqueExecuteJob,
            ],
        ]);

        $resqueQueueService
            ->shouldReceive('contains')
            ->with('scheduledjob-execute', ['id' => $scheduledJobId])
            ->andReturn(false);

        $resqueQueueService
            ->shouldReceive('enqueue')
            ->withArgs(function (ExecuteJob $executeJob) use ($scheduledJobId) {
                $this->assertEquals($scheduledJobId, $executeJob->args['id']);

                return true;
            });

        $command = new ExecuteCommand(
            MockFactory::createApplicationStateService(true),
            $resqueQueueService,
            MockFactory::createJobStartService(),
            MockFactory::createJobService(),
            \Mockery::mock(ScheduledJobRepository::class)
        );

        $returnCode = $command->run(new ArrayInput([
            'id' => $scheduledJobId,
        ]), new BufferedOutput());

        $this->assertEquals(
            ExecuteCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}

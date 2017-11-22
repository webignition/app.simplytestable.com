<?php

namespace Tests\ApiBundle\Unit\Command\ScheduledJob\ExecuteCommand;

use SimplyTestable\ApiBundle\Resque\Job\ScheduledJob\ExecuteJob;
use Tests\ApiBundle\Factory\MockFactory;
use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ExecuteCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testRunInMaintenanceReadOnlyMode()
    {
        $scheduledJobId = 1;

        $resqueExecuteJob = \Mockery::mock(ExecuteJob::class);

        $resqueJobFactory = MockFactory::createResqueJobFactory([
            'create' => [
                'withArgs' => [
                    'scheduledjob-execute',
                    ['id' => $scheduledJobId]
                ],
                'return' => $resqueExecuteJob,
            ],
        ]);

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

        $command = new ExecuteCommand(
            MockFactory::createApplicationStateService(true),
            $resqueQueueService,
            $resqueJobFactory,
            MockFactory::createEntityManager(),
            MockFactory::createJobStartService(),
            MockFactory::createJobService()
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

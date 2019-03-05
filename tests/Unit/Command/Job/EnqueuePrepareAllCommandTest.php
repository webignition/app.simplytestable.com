<?php

namespace App\Tests\Unit\Command\Job;

use App\Command\Job\EnqueuePrepareAllCommand;
use App\Repository\JobRepository;
use App\Tests\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class EnqueuePrepareAllCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunInMaintenanceReadOnlyMode()
    {
        $command = new EnqueuePrepareAllCommand(
            MockFactory::createResqueQueueService(),
            MockFactory::createStateService(),
            MockFactory::createApplicationStateService(true),
            \Mockery::mock(JobRepository::class)
        );

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            EnqueuePrepareAllCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}

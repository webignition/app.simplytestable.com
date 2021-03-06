<?php

namespace App\Tests\Unit\Command\Task\Assign;

use App\Command\Task\Assign\CollectionCommand;
use App\Repository\TaskRepository;
use App\Tests\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CollectionCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunInMaintenanceReadOnlyMode()
    {
        $command = new CollectionCommand(
            MockFactory::createApplicationStateService(true),
            MockFactory::createEntityManager(),
            MockFactory::createTaskPreProcessorFactory(),
            MockFactory::createResqueQueueService(),
            MockFactory::createStateService(),
            MockFactory::createWorkerTaskAssignmentService(),
            MockFactory::createLogger(),
            \Mockery::mock(TaskRepository::class)
        );

        $returnCode = $command->run(new ArrayInput([
            'ids' => '1,2,3'
        ]), new BufferedOutput());

        $this->assertEquals(
            CollectionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}

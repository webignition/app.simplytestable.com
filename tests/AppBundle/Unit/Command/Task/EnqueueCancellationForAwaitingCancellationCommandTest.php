<?php

namespace Tests\AppBundle\Unit\Command\Task;

use AppBundle\Command\Task\EnqueueCancellationForAwaitingCancellationCommand;
use Tests\AppBundle\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class EnqueueCancellationForAwaitingCancellationCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunInMaintenanceReadOnlyMode()
    {
        $command = new EnqueueCancellationForAwaitingCancellationCommand(
            MockFactory::createApplicationStateService(true),
            MockFactory::createEntityManager(),
            MockFactory::createStateService(),
            MockFactory::createResqueQueueService()
        );

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            EnqueueCancellationForAwaitingCancellationCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}

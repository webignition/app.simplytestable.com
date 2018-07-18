<?php

namespace Tests\AppBundle\Unit\Command\Job;

use AppBundle\Command\Job\CompleteAllWithNoIncompleteTasksCommand;
use Tests\AppBundle\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CompleteAllWithNoIncompleteTasksCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunInMaintenanceReadOnlyMode()
    {
        $command = new CompleteAllWithNoIncompleteTasksCommand(
            MockFactory::createApplicationStateService(true),
            MockFactory::createJobService()
        );

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            CompleteAllWithNoIncompleteTasksCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}

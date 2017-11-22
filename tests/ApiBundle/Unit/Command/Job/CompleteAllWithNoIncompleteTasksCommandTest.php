<?php

namespace Tests\ApiBundle\Unit\Command\Job;

use SimplyTestable\ApiBundle\Command\Job\CompleteAllWithNoIncompleteTasksCommand;
use Tests\ApiBundle\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CompleteAllWithNoIncompleteTasksCommandTest extends \PHPUnit_Framework_TestCase
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

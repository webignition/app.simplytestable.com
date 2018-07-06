<?php

namespace Tests\ApiBundle\Unit\Command\Tasks;

use SimplyTestable\ApiBundle\Command\Tasks\RequeueQueuedForAssignmentCommand;
use Tests\ApiBundle\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RequeueQueuedForAssignmentCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunInMaintenanceReadOnlyMode()
    {
        $command = new RequeueQueuedForAssignmentCommand(
            MockFactory::createApplicationStateService(true),
            MockFactory::createStateService(),
            MockFactory::createEntityManager()
        );

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            RequeueQueuedForAssignmentCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}

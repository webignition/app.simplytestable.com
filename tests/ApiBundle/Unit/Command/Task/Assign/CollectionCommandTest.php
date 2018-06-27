<?php

namespace Tests\ApiBundle\Unit\Command\Task\Assign;

use SimplyTestable\ApiBundle\Command\Task\Assign\CollectionCommand;
use Tests\ApiBundle\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CollectionCommandTest extends \PHPUnit_Framework_TestCase
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
            MockFactory::createLogger()
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

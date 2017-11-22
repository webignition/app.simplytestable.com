<?php

namespace Tests\ApiBundle\Unit\Command\Task\Cancel;

use SimplyTestable\ApiBundle\Command\Task\Cancel\CollectionCommand;
use Tests\ApiBundle\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CollectionCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testRunInMaintenanceReadOnlyMode()
    {
        $command = new CollectionCommand(
            MockFactory::createApplicationStateService(true),
            MockFactory::createTaskService(),
            MockFactory::createWorkerTaskCancellationService(),
            MockFactory::createLogger(),
            MockFactory::createEntityManager()
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

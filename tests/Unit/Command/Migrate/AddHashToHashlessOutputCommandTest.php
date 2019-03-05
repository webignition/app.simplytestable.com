<?php

namespace App\Tests\Unit\Command\Migrate;

use App\Command\Migrate\AddHashToHashlessOutputCommand;
use App\Repository\TaskOutputRepository;
use App\Tests\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class AddHashToHashlessOutputCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunCommandInMaintenanceReadOnlyMode()
    {
        $command = new AddHashToHashlessOutputCommand(
            MockFactory::createApplicationStateService(true),
            MockFactory::createEntityManager(),
            \Mockery::mock(TaskOutputRepository::class)
        );

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            AddHashToHashlessOutputCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}

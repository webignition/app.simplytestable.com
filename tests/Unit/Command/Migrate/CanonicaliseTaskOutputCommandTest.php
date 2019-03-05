<?php

namespace App\Tests\Unit\Command\Migrate;

use App\Command\Migrate\CanonicaliseTaskOutputCommand;
use App\Repository\TaskOutputRepository;
use App\Repository\TaskRepository;
use App\Tests\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CanonicaliseTaskOutputCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunCommandInMaintenanceReadOnlyMode()
    {
        $command = new CanonicaliseTaskOutputCommand(
            MockFactory::createApplicationStateService(true),
            MockFactory::createEntityManager(),
            \Mockery::mock(TaskRepository::class),
            \Mockery::mock(TaskOutputRepository::class)
        );

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            CanonicaliseTaskOutputCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}

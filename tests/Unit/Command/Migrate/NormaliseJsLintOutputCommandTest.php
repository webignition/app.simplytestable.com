<?php

namespace App\Tests\Unit\Command\Migrate;

use App\Command\Migrate\NormaliseJsLintOutputCommand;
use App\Tests\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class NormaliseJsLintOutputCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunCommandInMaintenanceReadOnlyModeReturnsStatusCode1()
    {
        $command = new NormaliseJsLintOutputCommand(
            MockFactory::createApplicationStateService(true),
            MockFactory::createEntityManager(),
            MockFactory::createTaskTypeService()
        );

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            NormaliseJsLintOutputCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}

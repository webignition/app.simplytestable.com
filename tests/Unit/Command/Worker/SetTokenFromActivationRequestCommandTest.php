<?php

namespace App\Tests\Unit\Command\Worker;

use App\Command\Worker\SetTokenFromActivationRequestCommand;
use App\Tests\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class SetTokenFromActivationRequestCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunInMaintenanceReadOnlyMode()
    {
        $command = new SetTokenFromActivationRequestCommand(
            MockFactory::createApplicationStateService(true),
            MockFactory::createEntityManager()
        );

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            SetTokenFromActivationRequestCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}

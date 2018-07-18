<?php

namespace App\Tests\Unit\Command\Worker;

use App\Command\Worker\ActivateVerifyCommand;
use App\Tests\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ActivateVerifyCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunInMaintenanceReadOnlyMode()
    {
        $command = new ActivateVerifyCommand(
            MockFactory::createApplicationStateService(true),
            MockFactory::createWorkerActivationRequestService(),
            MockFactory::createEntityManager()
        );

        $returnCode = $command->run(new ArrayInput([
            'id' => 1,
        ]), new BufferedOutput());

        $this->assertEquals(
            ActivateVerifyCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}

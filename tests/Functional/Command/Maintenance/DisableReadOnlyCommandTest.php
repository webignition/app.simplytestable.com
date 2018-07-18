<?php

namespace App\Tests\Functional\Command\Maintenance;

use App\Command\Maintenance\AbstractApplicationStateChangeCommand;
use App\Command\Maintenance\DisableReadOnlyCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class DisableReadOnlyCommandTest extends AbstractApplicationStateChangeTest
{
    public function testRetrieveService()
    {
        $this->assertInstanceOf(
            DisableReadOnlyCommand::class,
            self::$container->get(DisableReadOnlyCommand::class)
        );
    }

    /**
     * @dataProvider changeApplicationStateDataProvider
     *
     * @param bool $setStateReturnValue
     * @param int $expectedReturnCode
     */
    public function testRun($setStateReturnValue, $expectedReturnCode)
    {
        $command = new DisableReadOnlyCommand($this->createApplicationStateService(
            AbstractApplicationStateChangeCommand::STATE_ACTIVE,
            $setStateReturnValue
        ));

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals($expectedReturnCode, $returnCode);
    }
}

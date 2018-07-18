<?php

namespace Tests\ApiBundle\Functional\Command\Maintenance;

use SimplyTestable\ApiBundle\Command\Maintenance\AbstractApplicationStateChangeCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\DisableReadOnlyCommand;
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

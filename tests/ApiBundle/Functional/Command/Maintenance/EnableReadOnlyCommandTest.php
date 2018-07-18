<?php

namespace Tests\ApiBundle\Functional\Command\Maintenance;

use SimplyTestable\ApiBundle\Command\Maintenance\AbstractApplicationStateChangeCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class EnableReadOnlyCommandTest extends AbstractApplicationStateChangeTest
{
    public function testRetrieveService()
    {
        $this->assertInstanceOf(
            EnableReadOnlyCommand::class,
            self::$container->get(EnableReadOnlyCommand::class)
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
        $command = new EnableReadOnlyCommand($this->createApplicationStateService(
            AbstractApplicationStateChangeCommand::STATE_MAINTENANCE_READ_ONLY,
            $setStateReturnValue
        ));

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals($expectedReturnCode, $returnCode);
    }
}

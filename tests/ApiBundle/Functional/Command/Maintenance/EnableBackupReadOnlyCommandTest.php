<?php

namespace Tests\ApiBundle\Functional\Command\Maintenance;

use SimplyTestable\ApiBundle\Command\Maintenance\AbstractApplicationStateChangeCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\EnableBackupReadOnlyCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class EnableBackupReadOnlyCommandTest extends AbstractApplicationStateChangeTest
{
    public function testRetrieveService()
    {
        $this->assertInstanceOf(
            EnableBackupReadOnlyCommand::class,
            $this->container->get(EnableBackupReadOnlyCommand::class)
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
        $command = new EnableBackupReadOnlyCommand($this->createApplicationStateService(
            AbstractApplicationStateChangeCommand::STATE_MAINTENANCE_BACKUP_READ_ONLY,
            $setStateReturnValue
        ));

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals($expectedReturnCode, $returnCode);
    }
}

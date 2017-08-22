<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Maintenance;

use SimplyTestable\ApiBundle\Tests\Functional\ConsoleCommandTestCase;

class EnableBackupReadOnlyCommandTest extends ConsoleCommandTestCase {

    const STATE_FILE_RELATIVE_PATH = '/test';

    /**
     *
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:maintenance:enable-backup-read-only';
    }


    /**
     *
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {
        return array(
            new \SimplyTestable\ApiBundle\Command\Maintenance\EnableBackupReadOnlyCommand()
        );
    }

    public function testEnableBackupReadOnly() {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');

        $this->assertReturnCode(0);
        $this->assertEquals('maintenance-backup-read-only', $applicationStateService->getState());
        $this->assertFalse($applicationStateService->isInActiveState());
        $this->assertFalse($applicationStateService->isInMaintenanceReadOnlyState());
        $this->assertTrue($applicationStateService->isInMaintenanceBackupReadOnlyState());
    }
}

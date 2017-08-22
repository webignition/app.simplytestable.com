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
        $this->assertReturnCode(0);
        $this->assertEquals('maintenance-backup-read-only', $this->getApplicationStateService()->getState());
        $this->assertFalse($this->getApplicationStateService()->isInActiveState());
        $this->assertFalse($this->getApplicationStateService()->isInMaintenanceReadOnlyState());
        $this->assertTrue($this->getApplicationStateService()->isInMaintenanceBackupReadOnlyState());
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\ApplicationStateService
     */
    protected function getApplicationStateService() {
        $applicationStateService = $this->container->get('simplytestable.services.applicationStateService');

        return $applicationStateService;
    }


    /**
     *
     * @return string
     */
    private function getStateResourcePath() {
        return $this->container->get('kernel')->locateResource('@SimplyTestableApiBundle/Resources/config/state') . self::STATE_FILE_RELATIVE_PATH;
    }

}

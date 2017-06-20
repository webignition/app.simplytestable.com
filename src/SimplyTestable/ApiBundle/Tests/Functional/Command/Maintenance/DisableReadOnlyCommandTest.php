<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Maintenance;

use SimplyTestable\ApiBundle\Tests\Functional\ConsoleCommandTestCase;

class DisableReadOnlyCommandTest extends ConsoleCommandTestCase {

    const STATE_FILE_RELATIVE_PATH = '/test';

    /**
     *
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:maintenance:disable-read-only';
    }


    /**
     *
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {
        return array(
            new \SimplyTestable\ApiBundle\Command\Maintenance\DisableReadOnlyCommand()
        );
    }

    public function testDisableReadOnly() {
        $this->assertReturnCode(0);
        $this->assertEquals('active', $this->getApplicationStateService()->getState());
        $this->assertTrue($this->getApplicationStateService()->isInActiveState());
        $this->assertFalse($this->getApplicationStateService()->isInMaintenanceReadOnlyState());
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\ApplicationStateService
     */
    protected function getApplicationStateService() {
        $applicationStateService = $this->container->get('simplytestable.services.applicationStateService');
        $applicationStateService->setStateResourcePath($this->getStateResourcePath());

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

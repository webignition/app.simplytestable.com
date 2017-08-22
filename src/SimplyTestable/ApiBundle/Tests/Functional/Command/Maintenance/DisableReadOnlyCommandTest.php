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
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');

        $this->assertReturnCode(0);
        $this->assertEquals('active', $applicationStateService->getState());
        $this->assertTrue($applicationStateService->isInActiveState());
        $this->assertFalse($applicationStateService->isInMaintenanceReadOnlyState());
    }
}

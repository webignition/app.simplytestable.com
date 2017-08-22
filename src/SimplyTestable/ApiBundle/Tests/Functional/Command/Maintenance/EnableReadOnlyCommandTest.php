<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Maintenance;

use SimplyTestable\ApiBundle\Tests\Functional\ConsoleCommandTestCase;

class EnableReadOnlyCommandTest extends ConsoleCommandTestCase {

    const STATE_FILE_RELATIVE_PATH = '/test';

    /**
     *
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:maintenance:enable-read-only';
    }


    public function testEnableReadOnly() {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');

        $this->assertReturnCode(0);
        $this->assertEquals('maintenance-read-only', $applicationStateService->getState());
        $this->assertFalse($applicationStateService->isInActiveState());
        $this->assertTrue($applicationStateService->isInMaintenanceReadOnlyState());
    }
}

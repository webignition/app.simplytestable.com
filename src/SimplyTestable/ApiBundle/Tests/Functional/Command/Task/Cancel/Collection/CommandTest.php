<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Cancel\Collection;

class CancelCollectionCommandTest extends BaseTest {

    public function testCancelInMaintenanceReadOnlyModeReturnsStatusCode1() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertReturnCode(1, array(
            'ids' => implode(',', array(1,2,3))
        ));
        $this->executeCommand('simplytestable:maintenance:disable-read-only');
    }

}

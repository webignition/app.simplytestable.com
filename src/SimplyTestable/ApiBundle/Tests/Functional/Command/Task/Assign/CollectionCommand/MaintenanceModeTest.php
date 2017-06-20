<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Assign\CollectionCommand;

class MaintenanceModeTest extends CollectionCommandTest {

    private $executeReturnCode = null;

    public function setUp() {
        parent::setUp();

        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->executeReturnCode = $this->execute(['ids' => implode([1,2,3], ',')]);
    }


    public function testExecuteReturnCodeIsMinus1() {
        $this->assertEquals(-1, $this->executeReturnCode);
    }

}
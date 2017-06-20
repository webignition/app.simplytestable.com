<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Worker\SetTokenFromActivationRequestCommand;

class MaintenanceModeTest extends CommandTest {

    public function testReturnCode() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertEquals(1, $this->executeCommand($this->getCommandName()));
    }

}
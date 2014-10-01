<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Tasks\RequestAction;

class MaintenanceModeTest extends RequestTest {

    public function testInMaintenanceModeReturns503() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName();

        $this->assertEquals(503, $response->getStatusCode());
    }
    
}
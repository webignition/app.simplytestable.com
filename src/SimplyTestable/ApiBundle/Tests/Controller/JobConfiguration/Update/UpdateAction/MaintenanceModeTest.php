<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Update\UpdateAction;

use SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Update\ActionTest;

class MaintenanceModeTest extends ActionTest {

    public function setUp() {
        parent::setUp();
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
    }

    public function testCreateInMaintenanceReadOnlyModeReturns503() {
        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController($this->getRequestPostData())->$methodName(
            $this->container->get('request'),
            self::LABEL
        );

        $this->assertEquals(503, $response->getStatusCode());
    }
    
}


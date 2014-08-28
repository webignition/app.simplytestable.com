<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserCreation\CreateAction;

use SimplyTestable\ApiBundle\Tests\Controller\UserCreation\ActionTest;

class MaintenanceModeTest extends ActionTest {

    const DEFAULT_EMAIL = 'user@example.com';
    const DEFAULT_PASSWORD = 'password';

    public function setUp() {
        parent::setUp();
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
    }

    public function testCreateInMaintenanceReadOnlyModeReturns503() {
        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController($this->getRequestPostData())->$methodName();

        $this->assertEquals(503, $response->getStatusCode());
    }


    protected function getRequestPostData() {
        return [
            'email' => self::DEFAULT_EMAIL,
            'password' => self::DEFAULT_PASSWORD
        ];
    }
    
}


<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\ActivateAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\ActionTest;

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


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'token' => 'foo'
        ];
    }

}


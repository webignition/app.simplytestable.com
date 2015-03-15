<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Create\CreateAction;

use SimplyTestable\ApiBundle\Tests\Controller\UserCreation\ActionTest;

class MaintenanceModeTest extends ActionTest {

    public function setUp() {
        parent::setUp();
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
    }

    public function testCreateInMaintenanceReadOnlyModeReturns503() {
        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController($this->getRequestPostData())->$methodName(
            $this->container->get('request')
        );

        $this->assertEquals(503, $response->getStatusCode());
    }


    protected function getRequestPostData() {
        return [
            'job-configuration' => 'foo',
            'schedule' => '* * * * *',
            'is-recurring' =>  '1'
        ];
    }
    
}


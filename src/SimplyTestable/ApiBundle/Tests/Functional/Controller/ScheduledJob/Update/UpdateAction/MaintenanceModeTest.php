<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Update\UpdateAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Update\ActionTest;

class MaintenanceModeTest extends ActionTest {

    public function setUp() {
        parent::setUp();
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
    }

    public function testCreateInMaintenanceReadOnlyModeReturns503() {
        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController($this->getRequestPostData())->$methodName(
            $this->container->get('request'),
            1
        );

        $this->assertEquals(503, $response->getStatusCode());
    }


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'id' => '1'
        ];
    }


    protected function getRequestPostData() {
        return [
            'job-configuration' => 'foo',
            'schedule' => '* * * * *',
            'is-recurring' =>  '1'
        ];
    }

}


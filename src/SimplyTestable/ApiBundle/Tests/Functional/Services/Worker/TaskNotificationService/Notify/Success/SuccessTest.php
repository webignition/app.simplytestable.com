<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Worker\TaskNotificationService\Notify\Success;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Worker\TaskNotificationService\Notify\NotifyTest;

abstract class SuccessTest extends NotifyTest {

    protected function getHttpFixtureItems() {
        $fixture = HttpFixtureFactory::createSuccessResponse();
        $fixtures = [];

        for ($count = 0; $count < $this->getWorkerCount(); $count++) {
            $fixtures[] = $fixture;
        }

        return $fixtures;
    }

    protected function getWorkerCount() {
        $classNameParts = explode('\\', get_class($this));
        return (int)str_replace(['Worker', 'Test'], '', $classNameParts[count($classNameParts) - 1]);
    }

    public function testNotifyReturnValueIsTrue() {
        $this->assertTrue($this->notifyReturnValue);
    }


    public function testAllWorkersAreNotified() {
        $requests = [];
        foreach ($this->getHttpClientService()->getHistoryPlugin()->getAll() as $requestResponse) {
            $requests[] = $requestResponse['request'];
        }

        foreach ($this->workers as $workerIndex => $worker) {
            $this->assertEquals($requests[$workerIndex]->getHeaders()->get('host'), $worker->getHostname());
        }
    }

}

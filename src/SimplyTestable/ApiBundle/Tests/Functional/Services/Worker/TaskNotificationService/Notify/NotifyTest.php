<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Worker\TaskNotificationService\Notify;

use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Worker\TaskNotificationService\ServiceTest;

abstract class NotifyTest extends ServiceTest {

    /**
     * @var Worker[]
     */
    protected $workers;

    protected $notifyReturnValue;

    public function setUp() {
        parent::setUp();
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureItems()));

        $this->workers = $this->createWorkers($this->getWorkerCount());

        $this->notifyReturnValue = $this->getService()->notify();
    }

    abstract protected function getHttpFixtureItems();
    abstract protected function getWorkerCount();

//    public function testTest() {
//
//
//        $this->queueHttpFixtures($this->buildHttpFixtureSet([
//            'HTTP/1.1 200 OK'
//        ]));
//
//        $this->getService()->notify();
//    }

}

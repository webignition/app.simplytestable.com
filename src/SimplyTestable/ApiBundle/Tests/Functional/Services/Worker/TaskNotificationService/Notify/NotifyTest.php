<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Worker\TaskNotificationService\Notify;

use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Worker\TaskNotificationService\ServiceTest;

abstract class NotifyTest extends ServiceTest
{
    /**
     * @var Worker[]
     */
    protected $workers;

    protected $notifyReturnValue;

    protected function setUp()
    {
        parent::setUp();
        $this->queueHttpFixtures($this->getHttpFixtureItems());

        $this->workers = $this->createWorkers($this->getWorkerCount());

        $this->notifyReturnValue = $this->getService()->notify();
    }

    abstract protected function getHttpFixtureItems();
    abstract protected function getWorkerCount();
}

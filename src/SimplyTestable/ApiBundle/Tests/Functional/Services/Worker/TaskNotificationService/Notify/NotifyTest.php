<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Worker\TaskNotificationService\Notify;

use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;
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

        $workerFactory = new WorkerFactory($this->container);
        $this->workers = $workerFactory->createCollection($this->getWorkerCount());

        $this->notifyReturnValue = $this->getService()->notify();
    }

    abstract protected function getHttpFixtureItems();
    abstract protected function getWorkerCount();
}

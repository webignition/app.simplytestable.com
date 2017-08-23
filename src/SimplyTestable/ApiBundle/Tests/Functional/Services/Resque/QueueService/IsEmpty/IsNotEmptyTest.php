<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Resque\QueueService\IsEmpty;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Resque\QueueService\ServiceTest as BaseServiceTest;

class IsNotEmptyTest extends BaseServiceTest {

    protected function setUp() {
        parent::setUp();
        $this->clearRedis();

        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');

        $resqueQueueService->enqueue(
            $resqueJobFactory->create(
                'tasks-notify'
            )
        );
    }

    public function testIsEmpty() {
        $this->assertFalse($this->getService()->isEmpty('tasks-notify'));
    }

}

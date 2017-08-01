<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Resque\QueueService\Contains;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Resque\QueueService\ServiceTest as BaseServiceTest;

abstract class ServiceTest extends BaseServiceTest {

    /**
     * @var bool
     */
    private $containsResult;

    protected function setUp() {
        parent::setUp();

        $this->clearRedis();

        $this->getResqueQueueService()->enqueue(
            $this->getResqueJobFactoryService()->create(
                $this->getCreateQueueName(),
                $this->getCreateArgs()
            )
        );

        $this->containsResult = $this->getService()->contains($this->getContainsQueueName(), $this->getContainsArgs());
    }

    abstract protected function getCreateQueueName();
    abstract protected function getContainsQueueName();
    abstract protected function getExpectedDoesContain();

    protected function getContainsArgs() {
        return [];
    }

    protected function getCreateArgs() {
        return [];
    }

    public function testContainsResult() {
        $this->assertEquals($this->getExpectedDoesContain(), $this->containsResult);
    }

}

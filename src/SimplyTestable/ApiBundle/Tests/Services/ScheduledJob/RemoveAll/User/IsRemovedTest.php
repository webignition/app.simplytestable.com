<?php

namespace SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\RemoveAll\User;


class IsRemovedTest extends UserTest {

    public function setUp() {
        parent::setUp();
        $this->getScheduledJobService()->removeAll();
    }

    public function testScheduledJobIdIsNotSet() {
        $this->assertNull($this->scheduledJob->getId());
    }

    public function testCronJobIdIsNotSet() {
        $this->assertNull($this->scheduledJob->getCronJob()->getId());
    }
}
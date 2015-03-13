<?php

namespace SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\Get;

abstract class IsRetrievedTest extends WithTest {

    public function testRetrievedJob() {
        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\ScheduledJob', $this->scheduledJob);
    }
}
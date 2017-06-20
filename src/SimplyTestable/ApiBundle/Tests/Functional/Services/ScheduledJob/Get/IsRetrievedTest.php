<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Get;

abstract class IsRetrievedTest extends WithTest {

    public function testRetrievedJob() {
        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\ScheduledJob', $this->scheduledJob);
    }
}
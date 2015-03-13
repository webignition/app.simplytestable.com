<?php

namespace SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\Get;

abstract class IsNotRetrievedTest extends WithTest {

    public function testRetrievedJob() {
        $this->assertNull($this->scheduledJob);
    }
}
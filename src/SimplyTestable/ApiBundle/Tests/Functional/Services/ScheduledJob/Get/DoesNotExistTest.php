<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Get;

class DoesNotExistTest extends ServiceTest {

    public function testCallForNonExistentScheduledReturnsNull() {
        $this->getScheduledJobService()->setUser($this->getUserService()->getPublicUser());
        $this->assertNull($this->getScheduledJobService()->get(1));
    }

}
<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Get;

class DoesNotExistTest extends ServiceTest {

    public function testCallForNonExistentScheduledReturnsNull() {
        $userService = $this->container->get('simplytestable.services.userservice');

        $this->setUser($userService->getPublicUser());

        $this->assertNull($this->getScheduledJobService()->get(1));
    }

}
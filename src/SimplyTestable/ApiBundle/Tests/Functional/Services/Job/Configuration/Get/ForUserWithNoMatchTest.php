<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Get;

class ForUserWithNoMatchTest extends ServiceTest {

    public function testNoMatchReturnsNull() {
        $userService = $this->container->get('simplytestable.services.userservice');
        $this->getJobConfigurationService()->setUser($userService->getPublicUser());
        $this->assertNull($this->getJobConfigurationService()->get('foo'));
    }
}

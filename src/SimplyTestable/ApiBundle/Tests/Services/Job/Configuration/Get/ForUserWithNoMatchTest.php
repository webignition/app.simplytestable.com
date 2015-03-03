<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Get;

class ForUserWithNoMatchTest extends ServiceTest {

    public function testNoMatchReturnsNull() {
        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->assertNull($this->getJobConfigurationService()->get('foo'));
    }
}

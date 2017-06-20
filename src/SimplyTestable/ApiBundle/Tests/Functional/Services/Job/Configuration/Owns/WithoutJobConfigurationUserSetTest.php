<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Owns;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class WithoutJobConfigurationUserSetTest extends ServiceTest {

    public function testReturnsFalseIfJobConfigurationUserIsNotSet() {
        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->assertFalse($this->getJobConfigurationService()->owns(new JobConfiguration()));
    }

}
<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Owns;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class WithoutJobConfigurationUserSetTest extends ServiceTest {

    public function testReturnsFalseIfJobConfigurationUserIsNotSet() {
        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());
        $this->assertFalse($this->getJobConfigurationService()->owns(new JobConfiguration()));
    }

}
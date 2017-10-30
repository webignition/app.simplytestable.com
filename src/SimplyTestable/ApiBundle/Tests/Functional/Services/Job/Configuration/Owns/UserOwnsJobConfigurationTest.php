<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Owns;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class UserOwnsJobConfigurationTest extends ServiceTest {

    public function testReturnsTrueIfServiceUserEqualsJobConfigurationUser() {
        $userService = $this->container->get('simplytestable.services.userservice');
        $user = $userService->getPublicUser();

        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setUser($user);

        $this->setUser($user);

        $this->assertTrue($this->getJobConfigurationService()->owns($jobConfiguration));
    }

}

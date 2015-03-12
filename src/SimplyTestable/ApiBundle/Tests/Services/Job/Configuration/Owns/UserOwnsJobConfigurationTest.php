<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Owns;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class UserOwnsJobConfigurationTest extends ServiceTest {

    public function testReturnsTrueIfServiceUserEqualsJobConfigurationUser() {
        $user = $this->getUserService()->getPublicUser();

        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setUser($user);

        $this->getJobConfigurationService()->setUser($user);

        $this->assertTrue($this->getJobConfigurationService()->owns($jobConfiguration));
    }

}

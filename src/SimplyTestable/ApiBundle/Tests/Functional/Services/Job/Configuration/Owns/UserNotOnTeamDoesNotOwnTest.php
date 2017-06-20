<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Owns;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class UserNotOnTeamDoesNotOwnTest extends ServiceTest {

    public function testReturnsFalseIfUserNotOnTeamDoesNotOwn() {
        $user1 = $this->getUserService()->getPublicUser();
        $user2 = $this->createAndActivateUser('user@example.com');

        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setUser($user1);

        $this->getJobConfigurationService()->setUser($user2);

        $this->assertFalse($this->getJobConfigurationService()->owns($jobConfiguration));
    }

}

<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Owns;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class UserNotOnTeamDoesNotOwnTest extends ServiceTest {

    public function testReturnsFalseIfUserNotOnTeamDoesNotOwn() {
        $userFactory = new UserFactory($this->container);

        $user1 = $this->getUserService()->getPublicUser();
        $user2 = $userFactory->createAndActivateUser();

        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setUser($user1);

        $this->getJobConfigurationService()->setUser($user2);

        $this->assertFalse($this->getJobConfigurationService()->owns($jobConfiguration));
    }

}

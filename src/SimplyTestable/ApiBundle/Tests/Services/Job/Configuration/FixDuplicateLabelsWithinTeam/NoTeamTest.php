<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\FixDuplicateLabelsWithinTeam;

class NoTeamTest extends ServiceTest {

    const LABEL = 'foo';

    public function testCallForUserNotOnTeam() {
        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->getJobConfigurationService()->fixDuplicateLabelsWithinTeam();
    }

}
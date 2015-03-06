<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\NormaliseLabels;

class NoTeamTest extends ServiceTest {

    const LABEL = 'foo';

    public function testCallForUserNotOnTeam() {
        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->getJobConfigurationService()->normaliseLabels();
    }

}
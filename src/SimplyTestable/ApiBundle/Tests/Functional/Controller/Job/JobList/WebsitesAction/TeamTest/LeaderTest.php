<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\WebsitesAction\TeamTest;

use SimplyTestable\ApiBundle\Entity\User;

class LeaderTest extends TeamTest {


    /**
     * @return User
     */
    protected function getRequester() {
        return $this->leader;
    }

}
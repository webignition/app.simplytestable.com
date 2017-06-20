<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\WebsitesAction\TeamTest;

use SimplyTestable\ApiBundle\Entity\User;

class Member1Test extends TeamTest {


    /**
     * @return User
     */
    protected function getRequester() {
        return $this->member1;
    }

}
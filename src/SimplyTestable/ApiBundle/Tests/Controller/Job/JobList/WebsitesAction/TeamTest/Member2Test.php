<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction\TeamTest;

use SimplyTestable\ApiBundle\Entity\User;

class Member2Test extends TeamTest {


    /**
     * @return User
     */
    protected function getRequester() {
        return $this->member2;
    }

}
<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\TeamTest;

use SimplyTestable\ApiBundle\Entity\User;

class Member1Test extends TeamTest {


    /**
     * @return User
     */
    protected function getRequester() {
        return $this->member1;
    }

}
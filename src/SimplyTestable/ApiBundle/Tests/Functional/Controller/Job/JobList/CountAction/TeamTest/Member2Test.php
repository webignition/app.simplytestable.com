<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\CountAction\TeamTest;

use SimplyTestable\ApiBundle\Entity\User;

class Member2Test extends TeamTest {


    /**
     * @return User
     */
    protected function getRequester() {
        return $this->member2;
    }

}
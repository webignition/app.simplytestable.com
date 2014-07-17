<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\User\GetAction\HasInvite\Individual;

use SimplyTestable\ApiBundle\Tests\Controller\User\GetAction\HasInvite\HasInviteTest;

class NeverInvitedTest extends HasInviteTest {

    public function getUser() {
        return $this->createAndActivateUser('user@example.com');
    }


    public function getExpectedHasInvite() {
        return false;
    }
}



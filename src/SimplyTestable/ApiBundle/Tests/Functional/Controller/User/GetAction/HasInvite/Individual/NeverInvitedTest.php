<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction\HasInvite\Individual;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction\HasInvite\HasInviteTest;

class NeverInvitedTest extends HasInviteTest {

    public function getUser() {
        $userFactory = new UserFactory($this->container);

        return $userFactory->createAndActivateUser();
    }


    public function getExpectedHasInvite() {
        return false;
    }
}



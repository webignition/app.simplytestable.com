<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\User\GetAction\HasInvite\Individual;

use SimplyTestable\ApiBundle\Tests\Controller\User\GetAction\HasInvite\HasInviteTest;
use SimplyTestable\ApiBundle\Entity\User;

class NotAcceptedTest extends HasInviteTest {

    /**
     * @var User
     */
    private $user;

    /**
     * @return User
     */
    public function getUser() {
        return $this->user;
    }


    protected function preGetUser() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $this->getTeamService()->create('Foo', $leader);

        $this->user = $this->createAndActivateUser('user@example.com');

        $this->getTeamInviteService()->get($leader, $this->user);
    }


    public function getExpectedHasInvite() {
        return true;
    }
}
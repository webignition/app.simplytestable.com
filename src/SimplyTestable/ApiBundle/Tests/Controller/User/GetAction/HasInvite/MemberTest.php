<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\User\GetAction\HasInvite;

use SimplyTestable\ApiBundle\Entity\User;

class MemberTest extends HasInviteTest {

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


    public function getExpectedHasInvite() {
        return false;
    }


    protected function preGetUser() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $team = $this->getTeamService()->create('Foo', $leader);

        $this->user = $this->createAndActivateUser('user@example.com');

        $this->getTeamMemberService()->add($team, $this->user);
    }
}



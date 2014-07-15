<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\User\GetAction\InTeam;

use SimplyTestable\ApiBundle\Entity\User;

class MemberTest extends InTeamTest {

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


    public function getExpectedUserInTeam() {
        return true;
    }


    protected function preGetUser() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $team = $this->getTeamService()->create('Foo', $leader);

        $this->user = $this->createAndActivateUser('user@example.com');

        $this->getTeamMemberService()->add($team, $this->user);
    }
}



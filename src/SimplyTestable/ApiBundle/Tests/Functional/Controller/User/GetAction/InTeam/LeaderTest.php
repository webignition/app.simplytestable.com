<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction\InTeam;

use SimplyTestable\ApiBundle\Entity\User;

class LeaderTest extends InTeamTest {

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
        $this->user = $this->createAndActivateUser('leader@example.com');
        $this->getTeamService()->create('Foo', $this->user);
    }
}



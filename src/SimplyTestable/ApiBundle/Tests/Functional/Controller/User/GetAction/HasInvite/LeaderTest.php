<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction\HasInvite;

use SimplyTestable\ApiBundle\Entity\User;

class LeaderTest extends HasInviteTest {

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
        $this->user = $this->createAndActivateUser('leader@example.com');
        $this->getTeamService()->create('Foo', $this->user);
    }
}



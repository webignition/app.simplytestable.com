<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction\InTeam;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

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
        $userFactory = new UserFactory($this->container);

        $leader = $userFactory->createAndActivateUser('leader@example.com');
        $team = $this->getTeamService()->create('Foo', $leader);

        $this->user = $userFactory->createAndActivateUser();

        $this->getTeamMemberService()->add($team, $this->user);
    }
}



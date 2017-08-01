<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction\HasInvite;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

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
        $userFactory = new UserFactory($this->container);

        $this->user = $userFactory->createAndActivateUser('leader@example.com');
        $this->getTeamService()->create('Foo', $this->user);
    }
}



<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction\InTeam;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

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
        $userFactory = new UserFactory($this->container);

        $this->user = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $this->getTeamService()->create('Foo', $this->user);
    }
}



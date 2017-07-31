<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction\HasInvite\Individual;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction\HasInvite\HasInviteTest;
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
        $userFactory = new UserFactory($this->container);

        $leader = $userFactory->createAndActivateUser('leader@example.com');
        $this->getTeamService()->create('Foo', $leader);

        $this->user = $userFactory->createAndActivateUser();

        $this->getTeamInviteService()->get($leader, $this->user);
    }


    public function getExpectedHasInvite() {
        return true;
    }
}
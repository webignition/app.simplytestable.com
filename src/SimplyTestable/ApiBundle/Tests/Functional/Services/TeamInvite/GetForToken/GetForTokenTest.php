<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\TeamInvite\GetForToken;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamInvite\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

class GetForTokenTest extends ServiceTest {

    public function testInvalidTokenReturnsNoInvite() {
        $this->assertNull($this->getTeamInviteService()->getForToken('foo'));
    }


    public function testValidTokenReturnsInvite() {
        $userFactory = new UserFactory($this->container);

        $leader = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $user = $userFactory->createAndActivateUser();

        $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $invite = $this->getTeamInviteService()->get($leader, $user);

        $this->assertEquals($invite, $this->getTeamInviteService()->getForToken($invite->getToken()));
    }

}

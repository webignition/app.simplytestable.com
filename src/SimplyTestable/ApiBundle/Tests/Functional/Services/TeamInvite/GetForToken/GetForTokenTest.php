<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\TeamInvite\GetForToken;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamInvite\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

class GetForTokenTest extends ServiceTest {

    public function testInvalidTokenReturnsNoInvite() {
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');

        $this->assertNull($teamInviteService->getForToken('foo'));
    }


    public function testValidTokenReturnsInvite() {
        $teamService = $this->container->get('simplytestable.services.teamservice');
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');

        $userFactory = new UserFactory($this->container);

        $leader = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $user = $userFactory->createAndActivateUser();

        $teamService->create(
            'Foo1',
            $leader
        );

        $invite = $teamInviteService->get($leader, $user);

        $this->assertEquals($invite, $teamInviteService->getForToken($invite->getToken()));
    }

}

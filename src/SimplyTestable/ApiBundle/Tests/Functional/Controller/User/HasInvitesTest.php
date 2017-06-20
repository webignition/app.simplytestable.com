<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class HasInvitesTest extends BaseControllerJsonTestCase {

    public function testNonexistentUserHasNoInvites() {
        try {
            $controller = $this->getUserController('hasInvitesAction');
            $controller->hasInvitesAction('user@example.com');
            $this->fail('Attempt to check for invites for non-existent user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testUserWithNoInvitesHasNoInvites() {
        $user = $this->createAndActivateUser('user@example.com');

        try {
            $controller = $this->getUserController('hasInvitesAction');
            $controller->hasInvitesAction($user->getEmail());
            $this->fail('Attempt to check for invites for user with no invites did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testUserWithInvites() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $user = $this->createAndFindUser('user@example.com');

        $this->getTeamService()->create('Foo', $leader);
        $this->getTeamInviteService()->get($leader, $user);

        $this->assertEquals(
            200,
            $this->getUserController('hasInvitesAction')->hasInvitesAction($user->getEmail())->getStatusCode()
        );
    }
}



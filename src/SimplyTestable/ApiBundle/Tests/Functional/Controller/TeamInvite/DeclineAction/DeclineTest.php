<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\DeclineAction;

use SimplyTestable\ApiBundle\Controller\TeamInviteController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use Symfony\Component\HttpFoundation\Request;

class DeclineTest extends BaseControllerJsonTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var TeamInviteController
     */
    private $teamInviteController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->teamInviteController = new TeamInviteController();
        $this->teamInviteController->setContainer($this->container);
    }

    public function testUserDeclinesForNonexistentTeamReturnsOk() {
        $user = $this->userFactory->createAndActivateUser();
        $this->setUser($user);

        $request = new Request();
        $response = $this->teamInviteController->declineAction($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($this->getTeamInviteService()->hasAnyForUser($user));
        $this->assertFalse($this->getTeamMemberService()->belongsToTeam($user));
    }


    public function testUserHasNoInviteReturnsOk() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->userFactory->createAndActivateUser();
        $this->setUser($user);

        $request = new Request([], ['team' => 'Foo']);
        $response = $this->teamInviteController->declineAction($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($this->getTeamInviteService()->hasAnyForUser($user));
        $this->assertFalse($this->getTeamMemberService()->belongsToTeam($user));
    }


    public function testInviteIsDeclined() {
        $inviter = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'inviter@example.com',
        ]);
        $invitee = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $this->getTeamService()->create(
            'Foo',
            $inviter
        );

        $this->getTeamInviteService()->get($inviter, $invitee);

        $this->setUser($invitee);

        $request = new Request([], ['team' => 'Foo']);
        $response = $this->teamInviteController->declineAction($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($this->getTeamInviteService()->hasAnyForUser($invitee));
        $this->assertFalse($this->getTeamMemberService()->belongsToTeam($invitee));
    }


    protected function getRequestPostData() {
        return [
            'team' => 'Foo'
        ];
    }

}
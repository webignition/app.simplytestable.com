<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\GetAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use Symfony\Component\HttpFoundation\Request;

class GetTest extends BaseControllerJsonTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
    }

    public function testInviterIsNotTeamLeaderReturnsBadResponse() {
        $inviter = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'inviter@example.com',
        ]);
        $invitee = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $this->setUser($inviter);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName(new Request(), $invitee->getEmail());

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(1, $response->headers->get('X-TeamInviteGet-Error-Code'));
        $this->assertEquals('Inviter is not a team leader', $response->headers->get('X-TeamInviteGet-Error-Message'));
    }


    public function testInviteeIsNotAUserCreatesUserAndCreatesInvite() {
        $userService = $this->container->get('simplytestable.services.userservice');

        $inviter = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'inviter@example.com',
        ]);
        $team = $this->getTeamService()->create(
            'Foo',
            $inviter
        );


        $this->setUser($inviter);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName(new Request(), 'user@example.com');
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertTrue($userService->exists('user@example.com'));
        $user = $userService->findUserByEmail('user@example.com');

        $this->assertTrue($this->getTeamInviteService()->hasForTeamAnduser($team, $user));

        $invite = $this->getTeamInviteService()->getForTeamAndUser($team, $user);

        $responseObject = json_decode($response->getContent());

        $this->assertEquals($invite->getUser()->getEmail(), $responseObject->user);
        $this->assertEquals($invite->getTeam()->getName(), $responseObject->team);
    }


    public function testInviteeIsTeamLeaderReturnsBadResponse() {
        $inviter = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'inviter@example.com',
        ]);
        $invitee = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $this->setUser($inviter);

        $this->getTeamService()->create(
            'Foo1',
            $inviter
        );

        $this->getTeamService()->create(
            'Foo2',
            $invitee
        );

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName(new Request(), $invitee->getEmail());

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(2, $response->headers->get('X-TeamInviteGet-Error-Code'));
        $this->assertEquals('Invitee is a team leader', $response->headers->get('X-TeamInviteGet-Error-Message'));
    }


    public function testInviteeIsAlreadyOnADifferentTeamReturnsBadResponse() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $inviter = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'inviter@example.com',
        ]);
        $invitee = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $leaderTeam = $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $this->getTeamMemberService()->add($leaderTeam, $invitee);

        $this->getTeamService()->create(
            'Foo2',
            $inviter
        );

        $this->setUser($inviter);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName(new Request(), $invitee->getEmail());

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(3, $response->headers->get('X-TeamInviteGet-Error-Code'));
        $this->assertEquals('Invitee is on a team', $response->headers->get('X-TeamInviteGet-Error-Message'));
    }


    public function testGetNewInviteReturnsInvite() {
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

        $this->setUser($inviter);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName(new Request(), $invitee->getEmail());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull(json_decode($response->getContent(), true)['user']);
        $this->assertNotNull(json_decode($response->getContent(), true)['team']);
    }


    public function testGetExistingInviteReturnsInvite() {
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

        $this->setUser($inviter);

        $methodName = $this->getActionNameFromRouter();

        $response1 = $this->getCurrentController()->$methodName(new Request(), $invitee->getEmail());
        $response2 = $this->getCurrentController()->$methodName(new Request(), $invitee->getEmail());

        $this->assertEquals(json_decode($response1->getContent(), true)['team'], json_decode($response2->getContent(), true)['team']);
    }


    public function testInvitePublicUserReturnsBadRequest() {
        $userService = $this->container->get('simplytestable.services.userservice');

        $inviter = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'inviter@example.com',
        ]);

        $this->getTeamService()->create(
            'Foo',
            $inviter
        );

        $this->setUser($inviter);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName(new Request(), $userService->getPublicUser()->getEmail());

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(10, $response->headers->get('X-TeamInviteGet-Error-Code'));
        $this->assertEquals('Special users cannot be invited', $response->headers->get('X-TeamInviteGet-Error-Message'));
    }


    public function testInviteAdminUserReturnsBadRequest() {
        $userService = $this->container->get('simplytestable.services.userservice');

        $inviter = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'inviter@example.com',
        ]);

        $this->getTeamService()->create(
            'Foo',
            $inviter
        );

        $this->setUser($inviter);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName(new Request(), $userService->getAdminUser()->getEmail());

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(10, $response->headers->get('X-TeamInviteGet-Error-Code'));
        $this->assertEquals('Special users cannot be invited', $response->headers->get('X-TeamInviteGet-Error-Message'));
    }


    public function testInviteUserOnPremiumPlanReturnsBadRequest() {
        $inviter = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'inviter@example.com',
        ]);
        $invitee = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $this->getUserAccountPlanService()->subscribe($invitee, $this->getAccountPlanService()->find('personal'));

        $this->getTeamService()->create(
            'Foo',
            $inviter
        );

        $this->setUser($inviter);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName(new Request(), $invitee->getEmail());

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(11, $response->headers->get('X-TeamInviteGet-Error-Code'));
        $this->assertEquals('Invitee has a premium plan', $response->headers->get('X-TeamInviteGet-Error-Message'));
    }


    public function testTokenIsExposed() {
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

        $this->setUser($inviter);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName(new Request(), $invitee->getEmail());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull(json_decode($response->getContent(), true)['token']);
    }


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'invitee_email' => 'user@example.com'
        ];
    }

}
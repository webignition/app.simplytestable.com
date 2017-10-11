<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\AcceptAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class AcceptTest extends BaseControllerJsonTestCase
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


    public function testUserAcceptsForNonexistentTeamReturnsBadResponse() {
        $user = $this->userFactory->createAndActivateUser();
        $this->setUser($user);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName(
            $this->container->get('request')
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(1, $response->headers->get('X-TeamInviteAccept-Error-Code'));
        $this->assertEquals('Invalid team', $response->headers->get('X-TeamInviteAccept-Error-Message'));
    }

    public function testUserHasNoInviteReturnsBadResponse() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->userFactory->createAndActivateUser();
        $this->setUser($user);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController([
            'team' => 'Foo'
        ])->$methodName(
            $this->container->get('request')
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(2, $response->headers->get('X-TeamInviteAccept-Error-Code'));
        $this->assertEquals('User has not been invited to join this team', $response->headers->get('X-TeamInviteAccept-Error-Message'));
    }


    public function testInviteIsAccepted() {
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

        $invite = $this->getTeamInviteService()->get($inviter, $invitee);

        $this->setUser($invitee);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController([
            'team' => 'Foo'
        ])->$methodName(
            $this->container->get('request')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($invite->getId());
    }


    public function testAcceptedInviteRemovesAllInvites() {
        $leader1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader1@example.com',
        ]);
        $leader2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader2@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $this->getTeamService()->create(
            'Foo1',
            $leader1
        );

        $this->getTeamService()->create(
            'Foo2',
            $leader2
        );

        $invite1 = $this->getTeamInviteService()->get($leader1, $user);
        $invite2 = $this->getTeamInviteService()->get($leader2, $user);

        $this->assertTrue($this->getTeamInviteService()->hasAnyForUser($user));

        $this->setUser($user);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController([
            'team' => $invite1->getTeam()->getName()
        ])->$methodName(
            $this->container->get('request')
        );

        $this->assertFalse($this->getTeamInviteService()->hasAnyForUser($user));
        $this->assertNull($invite1->getId());
        $this->assertNull($invite2->getId());

    }


    public function testUserWithPremiumPlanCannotAcceptInvite() {
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

        $invite = $this->getTeamInviteService()->get($inviter, $invitee);

        $this->getUserAccountPlanService()->subscribe($invitee, $this->getAccountPlanService()->find('personal'));

        $this->setUser($invitee);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController([
            'team' => 'Foo'
        ])->$methodName(
            $this->container->get('request')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($invite->getId());

        $this->assertFalse($this->getTeamMemberService()->belongsToTeam($invitee));
    }

}
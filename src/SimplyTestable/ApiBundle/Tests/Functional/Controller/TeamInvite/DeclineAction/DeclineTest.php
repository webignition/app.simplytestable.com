<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\DeclineAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class DeclineTest extends BaseControllerJsonTestCase
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


    public function testUserDeclinesForNonexistentTeamReturnsOk() {
        $user = $this->userFactory->createAndActivateUser();
        $this->setUser($user);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName(
            $this->container->get('request')
        );

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

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController([
            'team' => 'Foo'
        ])->$methodName(
            $this->container->get('request')
        );

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

        $invite = $this->getTeamInviteService()->get($inviter, $invitee);

        $this->setUser($invitee);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController([
            'team' => 'Foo'
        ])->$methodName(
            $this->container->get('request')
        );

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
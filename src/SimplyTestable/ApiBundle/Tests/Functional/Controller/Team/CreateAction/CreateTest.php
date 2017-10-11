<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Team\CreateAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class CreateTest extends BaseControllerJsonTestCase
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

    public function testRequestAsTeamLeaderReturnsExistingTeam() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);

        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->setUser($leader);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController([
            'name' => 'Foo'
        ])->$methodName(
            $this->container->get('request')
        );

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/team/', $response->headers->get('location'));
        $this->assertEquals('Foo', $response->headers->get('X-Team-Name'));
    }


    public function testRequestAsTeamMemberReturnsExistingTeam() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->userFactory->createAndActivateUser();
        $this->setUser($user);

        $this->getTeamMemberService()->add($team, $user);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController([
            'name' => 'Foo'
        ])->$methodName(
            $this->container->get('request')
        );

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/team/', $response->headers->get('location'));
        $this->assertEquals('Foo', $response->headers->get('X-Team-Name'));
    }


    public function testRequestAsNonLeaderAndNonMemberCreatesNewTeam() {
        $user = $this->userFactory->createAndActivateUser();
        $this->setUser($user);

        $this->assertNull($this->getTeamService()->getForUser($user));

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController([
            'name' => 'Foo'
        ])->$methodName(
            $this->container->get('request')
        );

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/team/', $response->headers->get('location'));
        $this->assertEquals('Foo', $response->headers->get('X-Team-Name'));

        $this->assertNotNull($this->getTeamService()->getForUser($user));
    }


    public function testPublicUserCannotCreateTeam() {
        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController([
            'name' => 'Foo'
        ])->$methodName(
            $this->container->get('request')
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(9, $response->headers->get('X-TeamCreate-Error-Code'));
        $this->assertEquals('Special users cannot create teams', $response->headers->get('X-TeamCreate-Error-Message'));
    }


    public function testAdminUserCannotCreateTeam() {
        $userService = $this->container->get('simplytestable.services.userservice');

        $this->setUser($userService->getAdminUser());

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController([
            'name' => 'Foo'
        ])->$methodName(
            $this->container->get('request')
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(9, $response->headers->get('X-TeamCreate-Error-Code'));
        $this->assertEquals('Special users cannot create teams', $response->headers->get('X-TeamCreate-Error-Message'));
    }


    public function testUserInvitesDeletedOnTeamCreate() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $this->getTeamService()->create('Foo1', $leader);

        $this->getTeamInviteService()->get($leader, $user);

        $this->assertTrue($this->getTeamInviteService()->hasAnyForUser($user));

        $this->setUser($user);

        $methodName = $this->getActionNameFromRouter();

        $this->getCurrentController([
            'name' => 'Foo2'
        ])->$methodName(
            $this->container->get('request')
        );

        $this->assertFalse($this->getTeamInviteService()->hasAnyForUser($user));

    }

}
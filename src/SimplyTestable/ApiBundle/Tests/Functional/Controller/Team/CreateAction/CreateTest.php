<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Team\CreateAction;

use SimplyTestable\ApiBundle\Controller\TeamController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use Symfony\Component\HttpFoundation\Request;

class CreateTest extends BaseControllerJsonTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var TeamController
     */
    private $teamController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->teamController = new TeamController();
        $this->teamController->setContainer($this->container);
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

        $request = new Request([], ['name' => 'Foo']);
        $response = $this->teamController->createAction($request);

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

        $request = new Request([], ['name' => 'Foo']);
        $response = $this->teamController->createAction($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/team/', $response->headers->get('location'));
        $this->assertEquals('Foo', $response->headers->get('X-Team-Name'));
    }


    public function testRequestAsNonLeaderAndNonMemberCreatesNewTeam() {
        $user = $this->userFactory->createAndActivateUser();
        $this->setUser($user);

        $this->assertNull($this->getTeamService()->getForUser($user));

        $request = new Request([], ['name' => 'Foo']);
        $response = $this->teamController->createAction($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/team/', $response->headers->get('location'));
        $this->assertEquals('Foo', $response->headers->get('X-Team-Name'));

        $this->assertNotNull($this->getTeamService()->getForUser($user));
    }


    public function testPublicUserCannotCreateTeam() {
        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());

        $request = new Request([], ['name' => 'Foo']);
        $response = $this->teamController->createAction($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(9, $response->headers->get('X-TeamCreate-Error-Code'));
        $this->assertEquals('Special users cannot create teams', $response->headers->get('X-TeamCreate-Error-Message'));
    }


    public function testAdminUserCannotCreateTeam() {
        $userService = $this->container->get('simplytestable.services.userservice');

        $this->setUser($userService->getAdminUser());

        $request = new Request([], ['name' => 'Foo']);
        $response = $this->teamController->createAction($request);

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

        $request = new Request([], ['name' => 'Foo2']);
        $this->teamController->createAction($request);

        $this->assertFalse($this->getTeamInviteService()->hasAnyForUser($user));

    }

}
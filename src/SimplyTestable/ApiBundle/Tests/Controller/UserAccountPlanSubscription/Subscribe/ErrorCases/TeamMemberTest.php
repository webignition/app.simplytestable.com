<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserAccountPlanSubsciption\Subscribe\ErrorCases;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class TeamMemberTest extends BaseControllerJsonTestCase {

    private $response;

    public function setUp() {
        parent::setUp();

        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->createAndActivateUser('user@example.com', 'password');
        $this->getTeamMemberService()->add($team, $user);

        $this->getUserService()->setUser($user);

        $this->response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($user->getEmail(), 'personal');
    }

    public function testSubscribeFailsWithHttp400() {
        $this->assertEquals(400, $this->response->getStatusCode());
    }

    public function testResponseErrorMessage() {
        $this->assertEquals('User is a team member', $this->response->headers->get('X-Error-Message'));
    }

    public function testResponseErrorCode() {
        $this->assertEquals(1, $this->response->headers->get('X-Error-Code'));
    }
}



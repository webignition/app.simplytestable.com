<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\TeamInvite\ActivateAndAcceptAction;

use SimplyTestable\ApiBundle\Tests\Controller\TeamInvite\ActionTest;
use Symfony\Component\HttpFoundation\Response as SymfonyHttpResponse;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Team\Team;

class SuccessTest extends ActionTest {

    /**
     * @var SymfonyHttpResponse
     */
    private $response;


    /**
     * @var User
     */
    private $user;


    /**
     * @var Team
     */
    private $team;


    /**
     * @var string
     */
    private $initialPassword;


    public function setUp() {
        parent::setUp();

        $leader = $this->createAndActivateUser('leader@example.com');
        $this->user = $this->createAndFindUser('user@example.com');
        $this->initialPassword = $this->user->getPassword();

        $this->assertFalse($this->user->isEnabled());

        $this->team = $this->getTeamService()->create('Foo', $leader);
        $invite = $this->getTeamInviteService()->get($leader, $this->user);

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController([
            'token' => $invite->getToken(),
            'password' => 'foo'
        ])->$methodName();
    }


    public function testSuccessReturnsOk() {
        $this->assertEquals(200, $this->response->getStatusCode());
    }


    public function testUserIsActivated() {
        $this->assertTrue($this->user->isEnabled());
    }


    public function testUserJoinsTeam() {
        $this->assertEquals($this->team, $this->getTeamService()->getForUser($this->user));
    }


    public function testUserPasswordIsChanged() {
        $this->assertNotEquals($this->initialPassword, $this->user->getPassword());
    }

}
<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\ActivateAndAcceptAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use Symfony\Component\HttpFoundation\Response as SymfonyHttpResponse;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Team\Team;

class SuccessTest extends BaseControllerJsonTestCase {

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


    protected function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $leader = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $this->user = $userFactory->create();
        $this->initialPassword = $this->user->getPassword();

        $this->assertFalse($this->user->isEnabled());

        $this->team = $this->getTeamService()->create('Foo', $leader);
        $invite = $this->getTeamInviteService()->get($leader, $this->user);

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController([
            'token' => $invite->getToken(),
            'password' => 'foo'
        ])->$methodName(
            $this->container->get('request')
        );
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
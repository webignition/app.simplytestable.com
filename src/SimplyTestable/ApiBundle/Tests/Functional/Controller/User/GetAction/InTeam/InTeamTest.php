<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction\InTeam;

use SimplyTestable\ApiBundle\Controller\UserController;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Entity\User;

abstract class InTeamTest extends BaseControllerJsonTestCase {

    const DEFAULT_TRIAL_PERIOD = 30;


    /**
     * @var $user
     */
    private $user;


    /**
     * @var \stdClass
     */
    private $summary;


    /**
     * @return User
     */
    abstract protected function getUser();


    /**
     * @return bool
     */
    abstract protected function getExpectedUserInTeam();


    protected function preGetUser() {

    }


    protected function setUp() {
        parent::setUp();

        $this->preGetUser();

        $this->user = $this->getUser();

        $this->setUser($this->user);

        $userController = new UserController();
        $userController->setContainer($this->container);

        $response = $userController->getAction();

        $this->summary = json_decode($response->getContent());
    }


    public function testUserInTeam() {
        $this->assertEquals($this->getExpectedUserInTeam(), $this->summary->team_summary->in);
    }
}



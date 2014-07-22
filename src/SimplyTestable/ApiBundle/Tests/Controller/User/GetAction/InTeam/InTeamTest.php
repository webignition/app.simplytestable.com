<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\User\GetAction\InTeam;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;
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


    public function setUp() {
        parent::setUp();

        $this->preGetUser();

        $this->user = $this->getUser();

        $this->getUserService()->setUser($this->user);

        $actionMethod = $this->getActionNameFromRouter();

        $this->summary = json_decode($this->getCurrentController()->$actionMethod()->getContent());
    }


    public function testUserInTeam() {
        $this->assertEquals($this->getExpectedUserInTeam(), $this->summary->team_summary->in);
    }


    protected function getRouteParameters() {
        return [
            'email_canonical' => $this->user->getEmail()
        ];
    }
}


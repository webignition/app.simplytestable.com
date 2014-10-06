<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\CountAction\TeamTest;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Team;

abstract class TeamTest extends BaseControllerJsonTestCase {

    /**
     * @var User
     */
    protected $leader;


    /**
     * @var User
     */
    protected $member1;


    /**
     * @var User
     */
    protected $member2;


    /**
     * @var array
     */
    private $jobIds = [];


    /**
     * @var int
     */
    private $count;

    public function setUp() {
        parent::setUp();

        $this->leader = $this->createAndActivateUser('leader@example.com');
        $this->member1 = $this->createAndActivateUser('member1@example.com');
        $this->member2 = $this->createAndActivateUser('member2@example.com');

        $team = $this->getTeamService()->create('Foo', $this->leader);

        $this->getTeamMemberService()->add($team, $this->member1);
        $this->getTeamMemberService()->add($team, $this->member2);

        $this->jobIds[] = $this->createJobAndGetId('http://leader.example.com/', $this->leader->getEmail());
        $this->jobIds[] = $this->createJobAndGetId('http://member1.example.com/', $this->member1->getEmail());
        $this->jobIds[] = $this->createJobAndGetId('http://member2.example.com/', $this->member2->getEmail());

        $this->getUserService()->setUser($this->getRequester());

        $countResponse = $this->getJobListController('countAction')->countAction(count($this->jobIds));

        $this->count = json_decode($countResponse->getContent());
    }


    /**
     * @return User
     */
    abstract protected function getRequester();


    public function testCount() {
        $this->assertEquals(count($this->jobIds), $this->count);
    }
    
}
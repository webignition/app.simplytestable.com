<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\TeamTest;

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
     * @var \stdClass
     */
    private $list;

    public function setUp() {
        parent::setUp();

        $this->leader = $this->createAndActivateUser('leader@example.com');
        $this->member1 = $this->createAndActivateUser('member1@example.com');
        $this->member2 = $this->createAndActivateUser('member2@example.com');

        $this->getUserService()->setUser($this->getRequester());

        $team = $this->getTeamService()->create('Foo', $this->leader);

        $this->getTeamMemberService()->add($team, $this->member1);
        $this->getTeamMemberService()->add($team, $this->member2);

        $this->jobIds[] = $this->createJobAndGetId('http://leader.example.com/', $this->leader->getEmail());
        $this->jobIds[] = $this->createJobAndGetId('http://member1.example.com/', $this->member1->getEmail());
        $this->jobIds[] = $this->createJobAndGetId('http://member2.example.com/', $this->member2->getEmail());

        $listResponse = $this->getJobListController('listAction')->listAction(count($this->jobIds));

        $this->list = json_decode($listResponse->getContent());
    }


    /**
     * @return User
     */
    abstract protected function getRequester();


    public function testListJobCount() {
        $this->assertEquals(count($this->jobIds), count($this->list->jobs));
    }


    public function testListContainsLeaderJob() {
        $this->assertEquals('leader@example.com', $this->list->jobs[2]->user);
    }


    public function testListContainsMember1Job() {
        $this->assertEquals('member1@example.com', $this->list->jobs[1]->user);
    }


    public function testListContainsMember2Job() {
        $this->assertEquals('member2@example.com', $this->list->jobs[0]->user);
    }
    
}
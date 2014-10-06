<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\User\GetAction;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CreditsTest extends BaseControllerJsonTestCase {

    /**
     * @var User
     */
    private $leader;


    /**
     * @var User
     */
    private $member1;


    /**
     * @var User
     */
    private $member2;


    /**
     * @var int
     */
    private $expectedCreditsUsed = 0;

    public function setUp() {
        parent::setUp();

        $this->leader = $this->createAndActivateUser('leader@example.com');
        $this->member1 = $this->createAndActivateUser('member1@example.com');
        $this->member2 = $this->createAndActivateUser('member2@example.com');

        $team = $this->getTeamService()->create('Foo', $this->leader);
        $this->getTeamMemberService()->add($team, $this->member1);
        $this->getTeamMemberService()->add($team, $this->member2);

        $this->getUserService()->setUser($this->leader);
        $job1 = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, $this->leader->getEmail()));
        $this->setJobTasksCompleted($job1);
        $this->completeJob($job1);

        $this->getUserService()->setUser($this->member1);
        $job2 = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, $this->member1->getEmail()));
        $this->setJobTasksCompleted($job2);
        $this->completeJob($job2);

        $this->getUserService()->setUser($this->member2);
        $job3 = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, $this->member2->getEmail()));
        $this->setJobTasksCompleted($job3);
        $this->completeJob($job3);

        $this->expectedCreditsUsed = $job1->getTasks()->count() + $job2->getTasks()->count()  + $job3->getTasks()->count();
    }


    public function testLeaderCredits() {
        $this->getUserService()->setUser($this->leader);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals($this->expectedCreditsUsed, $responseObject->plan_constraints->credits->used);
    }


    public function testMember1Credits() {
        $this->getUserService()->setUser($this->member1);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals($this->expectedCreditsUsed, $responseObject->plan_constraints->credits->used);
    }


    public function testMember2Credits() {
        $this->getUserService()->setUser($this->member2);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals($this->expectedCreditsUsed, $responseObject->plan_constraints->credits->used);
    }
}



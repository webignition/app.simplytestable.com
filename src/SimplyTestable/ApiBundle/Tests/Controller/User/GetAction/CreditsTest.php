<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\User\GetAction;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CreditsTest extends BaseControllerJsonTestCase {

    const EXPECTED_CREDITS_USED = 12;

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

    public function setUp() {
        parent::setUp();

        $this->leader = $this->createAndActivateUser('leader@example.com');
        $this->member1 = $this->createAndActivateUser('member1@example.com');
        $this->member2 = $this->createAndActivateUser('member2@example.com');

        $team = $this->getTeamService()->create('Foo', $this->leader);
        $this->getTeamMemberService()->add($team, $this->member1);
        $this->getTeamMemberService()->add($team, $this->member2);

        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, $this->leader->getEmail()));
        $this->setJobTasksCompleted($job);
        $this->completeJob($job);
    }


    public function testLeaderCredits() {
        $this->getUserService()->setUser($this->leader);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals(self::EXPECTED_CREDITS_USED, $responseObject->plan_constraints->credits->used);
    }


    public function testMember1Credits() {
        $this->getUserService()->setUser($this->member1);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals(self::EXPECTED_CREDITS_USED, $responseObject->plan_constraints->credits->used);
    }


    public function testMember2Credits() {
        $this->getUserService()->setUser($this->member2);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals(self::EXPECTED_CREDITS_USED, $responseObject->plan_constraints->credits->used);
    }
}



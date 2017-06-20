<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\User\GetAction;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class CreditsTest extends BaseControllerJsonTestCase
{
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

    public function setUp()
    {
        parent::setUp();

        $this->leader = $this->createAndActivateUser('leader@example.com');
        $this->member1 = $this->createAndActivateUser('member1@example.com');
        $this->member2 = $this->createAndActivateUser('member2@example.com');

        $team = $this->getTeamService()->create('Foo', $this->leader);
        $this->getTeamMemberService()->add($team, $this->member1);
        $this->getTeamMemberService()->add($team, $this->member2);

        $jobFactory = $this->createJobFactory();

        $job1 = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $this->leader,
        ]);
        $this->completeJob($job1);

        $job2 = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $this->member1,
        ]);
        $this->completeJob($job2);

        $job3 = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $this->member2,
        ]);
        $this->completeJob($job3);

        $this->expectedCreditsUsed =
            $job1->getTasks()->count() + $job2->getTasks()->count()  + $job3->getTasks()->count();
    }

    public function testLeaderCredits()
    {
        $this->getUserService()->setUser($this->leader);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals($this->expectedCreditsUsed, $responseObject->plan_constraints->credits->used);
    }

    public function testMember1Credits()
    {
        $this->getUserService()->setUser($this->member1);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals($this->expectedCreditsUsed, $responseObject->plan_constraints->credits->used);
    }

    public function testMember2Credits()
    {
        $this->getUserService()->setUser($this->member2);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals($this->expectedCreditsUsed, $responseObject->plan_constraints->credits->used);
    }
}

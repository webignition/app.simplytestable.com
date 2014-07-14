<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\Team;

use SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class CreatedByTeamMemberAccessedByTeamMemberTest extends ServiceTest {

    const CANONICAL_URL = 'http://example.com/';

    /**
     * @var Job
     */
    private $job;

    public function setUp() {
        parent::setUp();

        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $member1 = $this->createAndActivateUser('user1@example.com');
        $member2 = $this->createAndActivateUser('user2@example.com');

        $this->getTeamMemberService()->add($team, $member1);
        $this->getTeamMemberService()->add($team, $member2);

        $this->job = $this->getJobService()->getById($this->getJobIdFromUrl($this->createJob(self::CANONICAL_URL, $member1->getEmail())->getTargetUrl()));

        $this->getJobRetrievalService()->setUser($member2);
    }


    public function testRetrieve() {
        $this->assertEquals($this->job->getId(), $this->getJobRetrievalService()->retrieve($this->job->getId())->getId());
    }

}
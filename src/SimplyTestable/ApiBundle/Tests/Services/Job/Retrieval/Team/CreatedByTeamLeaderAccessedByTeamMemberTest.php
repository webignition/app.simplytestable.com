<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\Team;

use SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class CreatedByTeamLeaderAccessedByTeamMemberTest extends ServiceTest {

    const CANONICAL_URL = 'http://example.com/';

    /**
     * @var Job
     */
    private $job;

    public function setUp() {
        parent::setUp();

        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $this->job = $this->getJobService()->getById($this->getJobIdFromUrl($this->createJob(self::CANONICAL_URL, $leader->getEmail())->getTargetUrl()));

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $member = $this->createAndActivateUser('user@example.com');

        $this->getTeamMemberService()->add($team, $member);

        $this->getJobRetrievalService()->setUser($member);
    }


    public function testRetrieve() {
        $this->assertEquals($this->job->getId(), $this->getJobRetrievalService()->retrieve($this->job->getId())->getId());
    }

}
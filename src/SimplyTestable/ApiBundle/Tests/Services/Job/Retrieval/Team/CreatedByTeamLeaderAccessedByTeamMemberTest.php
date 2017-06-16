<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\Team;

use SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class CreatedByTeamLeaderAccessedByTeamMemberTest extends ServiceTest
{
    /**
     * @var Job
     */
    private $job;

    public function setUp()
    {
        parent::setUp();

        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $this->job = $this->createJobFactory()->create(
            'full site',
            'http://example.com/',
            ['html validation',],
            [],
            [],
            $leader
        );

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $member = $this->createAndActivateUser('user@example.com');

        $this->getTeamMemberService()->add($team, $member);

        $this->getJobRetrievalService()->setUser($member);
    }

    public function testRetrieve()
    {
        $this->assertEquals(
            $this->job->getId(),
            $this->getJobRetrievalService()->retrieve($this->job->getId())->getId()
        );
    }
}

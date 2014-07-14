<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\Team;

use SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class CreatedByTeamLeaderAccessedByTeamLeaderTest extends ServiceTest {

    const CANONICAL_URL = 'http://example.com/';

    /**
     * @var Job
     */
    private $job;

    public function setUp() {
        parent::setUp();

        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->job = $this->getJobService()->getById($this->getJobIdFromUrl($this->createJob(self::CANONICAL_URL, $leader->getEmail())->getTargetUrl()));

        $this->getJobRetrievalService()->setUser($leader);
    }


    public function testRetrieve() {
        $this->assertEquals($this->job->getId(), $this->getJobRetrievalService()->retrieve($this->job->getId())->getId());
    }

}
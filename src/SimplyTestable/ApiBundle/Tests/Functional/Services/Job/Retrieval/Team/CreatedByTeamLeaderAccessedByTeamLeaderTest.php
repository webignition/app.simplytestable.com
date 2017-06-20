<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Retrieval\Team;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Retrieval\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class CreatedByTeamLeaderAccessedByTeamLeaderTest extends ServiceTest
{
    /**
     * @var Job
     */
    private $job;

    public function setUp()
    {
        parent::setUp();

        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->job = $this->createJobFactory()->create([
            JobFactory::KEY_USER => $leader,
        ]);

        $this->getJobRetrievalService()->setUser($leader);
    }

    public function testRetrieve()
    {
        $this->assertEquals(
            $this->job->getId(),
            $this->getJobRetrievalService()->retrieve($this->job->getId())->getId()
        );
    }
}

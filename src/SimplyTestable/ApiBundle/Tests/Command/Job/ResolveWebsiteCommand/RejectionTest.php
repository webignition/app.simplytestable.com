<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\ResolveWebsiteCommand;

use SimplyTestable\ApiBundle\Entity\Job\Job;

class RejectionTest extends CommandTest
{
    /**
     *
     * @var Job
     */
    private $job;

    public function setUp()
    {
        parent::setUp();

        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "CURL/28"
        )));

        $this->job = $this->createJobFactory()->create();

        $this->clearRedis();

        $this->assertReturnCode(0, array(
            'id' => $this->job->getId()
        ));
    }

    public function testJobStateIsRejected()
    {
        $this->assertEquals($this->getJobService()->getRejectedState(), $this->job->getState());
    }

    public function testNoTasksAreCreated()
    {
        $this->assertEquals(0, $this->job->getTasks()->count());
    }

    public function testResqueQueueDoesNotContainJobPreparationJob()
    {
        $this->assertFalse($this->getResqueQueueService()->contains(
            'job-prepare',
            array(
                'id' => $this->job->getId()
            )
        ));
    }
}

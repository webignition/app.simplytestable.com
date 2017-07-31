<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Job\ResolveWebsiteCommand;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class RejectionTest extends CommandTest
{
    /**
     *
     * @var Job
     */
    private $job;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "CURL/28"
        )));

        $jobFactory = new JobFactory($this->container);

        $this->job = $jobFactory->create();

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

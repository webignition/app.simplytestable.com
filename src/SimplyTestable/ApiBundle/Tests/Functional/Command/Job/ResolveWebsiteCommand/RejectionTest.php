<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Job\ResolveWebsiteCommand;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Tests\Factory\CurlExceptionFactory;
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
        $this->queueHttpFixtures([
            CurlExceptionFactory::create('Operation timed out', 28),
        ]);

        $jobFactory = new JobFactory($this->container);

        $this->job = $jobFactory->create();

        $this->clearRedis();

        $this->assertReturnCode(0, array(
            'id' => $this->job->getId()
        ));
    }

    public function testJobStateIsRejected()
    {
        $this->assertEquals(JobService::REJECTED_STATE, $this->job->getState()->getName());
    }

    public function testNoTasksAreCreated()
    {
        $this->assertEquals(0, $this->job->getTasks()->count());
    }

    public function testResqueQueueDoesNotContainJobPreparationJob()
    {
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $this->assertFalse($resqueQueueService->contains(
            'job-prepare',
            array(
                'id' => $this->job->getId()
            )
        ));
    }
}

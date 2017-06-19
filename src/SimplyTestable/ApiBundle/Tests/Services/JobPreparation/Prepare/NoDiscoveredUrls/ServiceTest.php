<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\NoDiscoveredUrls;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class ServiceTest extends BaseSimplyTestableTestCase
{
    /**
     * @var Job
     */
    private $job;

    public function setUp()
    {
        parent::setUp();

        $user = $this->getUserService()->getPublicUser();
        $this->getUserService()->setUser($user);

        $jobFactory = $this->createJobFactory();
        $this->job = $jobFactory->create();
        $jobFactory->resolve($this->job);

        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',
        )));

        $this->getJobPreparationService()->prepare($this->job);
    }

    public function testStateIsFailedNoSitemap()
    {
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState(), $this->job->getState());
    }

    public function testHasNoTasks()
    {
        $this->assertEquals(0, $this->job->getTasks()->count());
    }
}

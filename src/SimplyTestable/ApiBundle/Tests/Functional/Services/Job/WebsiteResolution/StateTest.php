<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class StateTest extends BaseSimplyTestableTestCase
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory($this->container);


        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK"
        )));
    }

    public function testFullSiteJobStateIsResolved()
    {
        $job = $this->jobFactory->create();

        $this->getJobWebsiteResolutionService()->resolve($job);
        $this->assertEquals($this->getJobService()->getResolvedState(), $job->getState());
    }

    public function testSingleUrlJobStateIsResolved()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
        ]);

        $this->getJobWebsiteResolutionService()->resolve($job);
        $this->assertEquals($this->getJobService()->getResolvedState(), $job->getState());
    }
}

<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class StateTest extends BaseSimplyTestableTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK"
        )));
    }

    public function testFullSiteJobStateIsResolved()
    {
        $job = $this->createJobFactory()->create();

        $this->getJobWebsiteResolutionService()->resolve($job);
        $this->assertEquals($this->getJobService()->getResolvedState(), $job->getState());
    }

    public function testSingleUrlJobStateIsResolved()
    {
        $job = $this->createJobFactory()->create([
            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
        ]);

        $this->getJobWebsiteResolutionService()->resolve($job);
        $this->assertEquals($this->getJobService()->getResolvedState(), $job->getState());
    }
}

<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class CUrlOptionsAreSetOnRequestsTest extends BaseSimplyTestableTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK"
        )));

        $jobFactory = new JobFactory($this->container);

        $this->getJobWebsiteResolutionService()->resolve($jobFactory->create());
    }

    public function testCurlOptionsAreSetOnAllRequests()
    {
        $this->assertSystemCurlOptionsAreSetOnAllRequests();
    }
}

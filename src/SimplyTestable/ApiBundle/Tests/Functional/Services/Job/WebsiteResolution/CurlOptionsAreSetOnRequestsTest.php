<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class CUrlOptionsAreSetOnRequestsTest extends BaseSimplyTestableTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse(),
        ]);

        $jobFactory = new JobFactory($this->container);

        $this->getJobWebsiteResolutionService()->resolve($jobFactory->create());
    }

    public function testCurlOptionsAreSetOnAllRequests()
    {
        $this->assertSystemCurlOptionsAreSetOnAllRequests();
    }
}

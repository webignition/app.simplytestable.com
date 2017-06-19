<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class CUrlOptionsAreSetOnRequestsTest extends BaseSimplyTestableTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK"
        )));

        $this->getJobWebsiteResolutionService()->resolve($this->createJobFactory()->create());
    }

    public function testCurlOptionsAreSetOnAllRequests()
    {
        $this->assertSystemCurlOptionsAreSetOnAllRequests();
    }
}

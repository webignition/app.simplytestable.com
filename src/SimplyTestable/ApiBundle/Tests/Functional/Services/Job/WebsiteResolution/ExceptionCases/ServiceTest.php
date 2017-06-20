<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution\ExceptionCases;

use SimplyTestable\ApiBundle\Exception\Services\Job\WebsiteResolutionException;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class ServiceTest extends BaseSimplyTestableTestCase
{
    const CANONICAL_URL = 'http://example.com';

    public function testJobInWrongStateThrowsJobWebsiteResolutionException()
    {
        $job = $this->createJobFactory()->create();
        $job->setState($this->getJobService()->getCancelledState());
        $this->getJobService()->persistAndFlush($job);

        try {
            $this->getJobWebsiteResolutionService()->resolve($job);
            $this->fail('\SimplyTestable\ApiBundle\Exception\Services\Job\WebsiteResolutionException not thrown');
        } catch (WebsiteResolutionException $websiteResolutionException) {
            $this->assertTrue($websiteResolutionException->isJobInWrongStateException());
        }
    }
}

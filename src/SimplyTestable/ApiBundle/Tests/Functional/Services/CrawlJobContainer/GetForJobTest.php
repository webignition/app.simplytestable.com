<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class GetForJobTest extends BaseSimplyTestableTestCase
{
    public function testGet()
    {
        $jobFactory = new JobFactory($this->container);

        $job = $jobFactory->create();

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);

        $this->assertNotNull($crawlJobContainer);
        $this->assertEquals($job->getId(), $crawlJobContainer->getParentJob()->getId());
    }
}

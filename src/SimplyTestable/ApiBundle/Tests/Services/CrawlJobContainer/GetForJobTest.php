<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class GetForJobTest extends BaseSimplyTestableTestCase
{
    public function testGet()
    {
        $job = $this->createJobFactory()->create();

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);

        $this->assertNotNull($crawlJobContainer);
        $this->assertEquals($job->getId(), $crawlJobContainer->getParentJob()->getId());
    }
}

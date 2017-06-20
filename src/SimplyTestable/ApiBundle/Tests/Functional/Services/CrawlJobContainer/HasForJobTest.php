<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class HasForJobTest extends BaseSimplyTestableTestCase
{
    public function testHasForParentJob()
    {
        $job = $this->createJobFactory()->create();
        $this->getCrawlJobContainerService()->getForJob($job);

        $this->assertTrue($this->getCrawlJobContainerService()->hasForJob($job));
    }

    public function testHasForCrawlJob()
    {
        $job = $this->createJobFactory()->create();
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);

        $this->assertTrue($this->getCrawlJobContainerService()->hasForJob($crawlJobContainer->getCrawlJob()));
    }

    public function testHasNot()
    {
        $job = $this->createJobFactory()->create();
        $this->assertFalse($this->getCrawlJobContainerService()->hasForJob($job));
    }
}

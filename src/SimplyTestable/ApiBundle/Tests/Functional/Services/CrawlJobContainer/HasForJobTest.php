<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class HasForJobTest extends BaseSimplyTestableTestCase
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
    }

    public function testHasForParentJob()
    {
        $job = $this->jobFactory->create();
        $this->getCrawlJobContainerService()->getForJob($job);

        $this->assertTrue($this->getCrawlJobContainerService()->hasForJob($job));
    }

    public function testHasForCrawlJob()
    {
        $job = $this->jobFactory->create();
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);

        $this->assertTrue($this->getCrawlJobContainerService()->hasForJob($crawlJobContainer->getCrawlJob()));
    }

    public function testHasNot()
    {
        $job = $this->jobFactory->create();
        $this->assertFalse($this->getCrawlJobContainerService()->hasForJob($job));
    }
}

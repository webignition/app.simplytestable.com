<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class GetForJobTest extends BaseSimplyTestableTestCase {

    public function testGet() {        
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));        
        $this->getCrawlJobContainerService()->create($job);
        
        $crawlJob = $this->getCrawlJobContainerService()->getForJob($job);
        
        $this->assertNotNull($crawlJob);
        $this->assertEquals($job->getId(), $crawlJob->getParentJob()->getId());
    }    

}

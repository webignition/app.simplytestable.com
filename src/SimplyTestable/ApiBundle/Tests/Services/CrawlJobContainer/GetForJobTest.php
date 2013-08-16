<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class GetForJobTest extends BaseSimplyTestableTestCase {

    public function testGet() {        
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));        
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        
        $this->assertNotNull($crawlJobContainer);
        $this->assertEquals($job->getId(), $crawlJobContainer->getParentJob()->getId());
    }    

}

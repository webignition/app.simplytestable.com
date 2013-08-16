<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class HasForJobTest extends BaseSimplyTestableTestCase {

    public function testHasForParentJob() {        
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));        
        $this->getCrawlJobContainerService()->getForJob($job);
        
        $this->assertTrue($this->getCrawlJobContainerService()->hasForJob($job));
    }
    
    public function testHasForCrawlJob() {        
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        
        $this->assertTrue($this->getCrawlJobContainerService()->hasForJob($crawlJobContainer->getCrawlJob()));
    }    
    
    public function testHasNot() {        
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));                        
        
        $this->assertFalse($this->getCrawlJobContainerService()->hasForJob($job));
    }    

}

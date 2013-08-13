<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class HasForJobTest extends BaseSimplyTestableTestCase {

    public function testHas() {        
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));        
        $this->getCrawlJobContainerService()->create($job);
        
        $this->assertTrue($this->getCrawlJobContainerService()->hasForJob($job));
    }
    
    public function testHasNot() {        
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));                        
        
        $this->assertFalse($this->getCrawlJobContainerService()->hasForJob($job));
    }    

}

<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJob;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class HasForJobTest extends BaseSimplyTestableTestCase {

    public function testHas() {        
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));        
        $this->getCrawlJobService()->create($job);
        
        $this->assertTrue($this->getCrawlJobService()->hasForJob($job));
    }
    
    public function testHasNot() {        
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));                        
        
        $this->assertFalse($this->getCrawlJobService()->hasForJob($job));
    }    

}

<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJob;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class GetForJobTest extends BaseSimplyTestableTestCase {

    public function testGet() {        
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));        
        $this->getCrawlJobService()->create($job);
        
        $crawlJob = $this->getCrawlJobService()->getForJob($job);
        
        $this->assertNotNull($crawlJob);
        $this->assertEquals($job->getId(), $crawlJob->getJob()->getId());
    }    

}

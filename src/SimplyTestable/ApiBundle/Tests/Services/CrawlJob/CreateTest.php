<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJob;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class CreateTest extends BaseSimplyTestableTestCase {

    public function testCreate() {        
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));        
        $crawlJob = $this->getCrawlJobService()->create($job);
        
        $this->assertInternalType('int', $crawlJob->getId());
        $this->assertTrue($crawlJob->getState()->equals($this->getCrawlJobService()->getQueuedState()));
    }

}

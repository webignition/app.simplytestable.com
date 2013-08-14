<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class CreateTest extends BaseSimplyTestableTestCase {

    public function testCreate() {        
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));        
        $crawlJobContainer = $this->getCrawlJobContainerService()->create($job);
        
        $this->assertInternalType('int', $crawlJobContainer->getId());
        $this->assertTrue($crawlJobContainer->getCrawlJob()->getState()->equals($this->getJobService()->getStartingState()));
    }

}

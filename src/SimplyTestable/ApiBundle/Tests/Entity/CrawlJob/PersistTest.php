<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Account\Plan;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Job\CrawlJob;

class PersistTest extends BaseSimplyTestableTestCase {

    public function testPersist() {
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));
        
        $crawlJob = new CrawlJob();
        $crawlJob->setJob($job);
        $crawlJob->setState($this->getCrawlJobService()->getQueuedState());
     
        $this->getEntityManager()->persist($crawlJob);        
        $this->getEntityManager()->flush();
        
        $this->assertNotNull($crawlJob->getId());               
    }
    
    
}

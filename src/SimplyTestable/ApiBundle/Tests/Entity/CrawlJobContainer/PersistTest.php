<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\CrawlJobContainer;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class PersistTest extends BaseSimplyTestableTestCase {

    public function testPersist() {
        $parentJob = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));
        
        $crawlJob = new Job();
        $crawlJob->setType($this->getJobTypeService()->getCrawlType());
        $crawlJob->setState($this->getJobService()->getStartingState());
        $crawlJob->setUser($parentJob->getUser());
        $crawlJob->setWebsite($parentJob->getWebsite());
        
        $this->getEntityManager()->persist($crawlJob);
        
        $crawlJobContainer = new CrawlJobContainer();
        $crawlJobContainer->setParentJob($parentJob);
        $crawlJobContainer->setCrawlJob($crawlJob);
        $crawlJobContainer->setState($this->getCrawlJobContainerService()->getQueuedState());
     
        $this->getEntityManager()->persist($crawlJobContainer);        
        $this->getEntityManager()->flush();
        
        $this->assertNotNull($crawlJobContainer->getId());               
    }
    
    
}

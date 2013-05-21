<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Account\Plan;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Ammendment;

class AmmendmentTest extends BaseSimplyTestableTestCase {

    public function testPersistWithNoConstraint() {
        $canonicalUrl = 'http://example.com/';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $rejectionReason = new Ammendment();
        $rejectionReason->setJob($job);
        $rejectionReason->setReason('url-count-limited');
      
        $this->getEntityManager()->persist($rejectionReason);
        $this->getEntityManager()->flush();
        
        $this->assertNotNull($rejectionReason->getId());
    }
    
    
    public function testPersistWithConstraint() {
        $canonicalUrl = 'http://example.com/';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $rejectionReason = new Ammendment();
        $rejectionReason->setJob($job);
        $rejectionReason->setReason('url-count-limited');
        $rejectionReason->setConstraint($this->createAccountPlanConstraint());
       
        $this->getEntityManager()->persist($rejectionReason);
        $this->getEntityManager()->flush();
        
        $this->assertNotNull($rejectionReason->getId());
    }    

}

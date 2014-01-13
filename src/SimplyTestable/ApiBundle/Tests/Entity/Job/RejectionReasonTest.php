<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Job;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Job\RejectionReason;

class RejectionReasonTest extends BaseSimplyTestableTestCase {
    
    public function testUtf8Reason() {
        $reason = 'ɸ';        
        $canonicalUrl = 'http://example.com/';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $rejectionReason = new RejectionReason();
        $rejectionReason->setJob($job);
        $rejectionReason->setReason($reason);
      
        $this->getEntityManager()->persist($rejectionReason);
        $this->getEntityManager()->flush();
        
        $rejectionReasonId = $rejectionReason->getId();
        
        $this->getEntityManager()->clear();
        
        $this->assertEquals($reason, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Job\RejectionReason')->find($rejectionReasonId)->getReason());         
    }     

    public function testPersistWithNoConstraint() {
        $canonicalUrl = 'http://example.com/';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $rejectionReason = new RejectionReason();
        $rejectionReason->setJob($job);
        $rejectionReason->setReason('insufficient-credit');
      
        $this->getEntityManager()->persist($rejectionReason);
        $this->getEntityManager()->flush();
        
        $this->assertNotNull($rejectionReason->getId());
    }
    
    
    public function testPersistWithConstraint() {
        $canonicalUrl = 'http://example.com/';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $rejectionReason = new RejectionReason();
        $rejectionReason->setJob($job);
        $rejectionReason->setReason('insufficient-credit');
        $rejectionReason->setConstraint($this->createAccountPlanConstraint());
       
        $this->getEntityManager()->persist($rejectionReason);
        $this->getEntityManager()->flush();
        
        $this->assertNotNull($rejectionReason->getId());
    }    

}

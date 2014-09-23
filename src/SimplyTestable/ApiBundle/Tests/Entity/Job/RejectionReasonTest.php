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
      
        $this->getManager()->persist($rejectionReason);
        $this->getManager()->flush();
        
        $rejectionReasonId = $rejectionReason->getId();
        
        $this->getManager()->clear();
        
        $this->assertEquals($reason, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Job\RejectionReason')->find($rejectionReasonId)->getReason());
    }     

    public function testPersistWithNoConstraint() {
        $canonicalUrl = 'http://example.com/';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $rejectionReason = new RejectionReason();
        $rejectionReason->setJob($job);
        $rejectionReason->setReason('insufficient-credit');
      
        $this->getManager()->persist($rejectionReason);
        $this->getManager()->flush();
        
        $this->assertNotNull($rejectionReason->getId());
    }
    
    
    public function testPersistWithConstraint() {
        $canonicalUrl = 'http://example.com/';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $rejectionReason = new RejectionReason();
        $rejectionReason->setJob($job);
        $rejectionReason->setReason('insufficient-credit');
        $rejectionReason->setConstraint($this->createAccountPlanConstraint());
       
        $this->getManager()->persist($rejectionReason);
        $this->getManager()->flush();
        
        $this->assertNotNull($rejectionReason->getId());
    }    

}

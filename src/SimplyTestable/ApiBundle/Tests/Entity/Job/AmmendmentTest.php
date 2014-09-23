<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Job;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Ammendment;

class AmmendmentTest extends BaseSimplyTestableTestCase {
    
    public function testUtf8Reason() {
        $reason = 'ɸ';        
        $canonicalUrl = 'http://example.com/';        
        
        $ammendment = new Ammendment();
        $ammendment->setJob($this->getJobService()->getById($this->createJobAndGetId($canonicalUrl)));
        $ammendment->setReason($reason);
      
        $this->getManager()->persist($ammendment);
        $this->getManager()->flush();
        
        $ammendmentId = $ammendment->getId();
        
        $this->getManager()->clear();
        
        $this->assertEquals($reason, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Job\Ammendment')->find($ammendmentId)->getReason());
    }    

    public function testPersistWithNoConstraint() {
        $canonicalUrl = 'http://example.com/';        
        
        $ammendment = new Ammendment();
        $ammendment->setJob($this->getJobService()->getById($this->createJobAndGetId($canonicalUrl)));
        $ammendment->setReason('url-count-limited');
      
        $this->getManager()->persist($ammendment);
        $this->getManager()->flush();
        
        $this->assertNotNull($ammendment->getId());               
    }
    
    
    public function testPersistWithConstraint() {
        $canonicalUrl = 'http://example.com/';   
        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $ammendment = new Ammendment();
        $ammendment->setJob($job);
        $ammendment->setReason('url-count-limited');
        $ammendment->setConstraint($this->createAccountPlanConstraint());        
       
        $this->getManager()->persist($ammendment);
        $this->getManager()->flush();
        
        $this->assertNotNull($ammendment->getId());        
    }
    
    
    public function testJobAmmendmentCountWithOneAmmendment() {
        $canonicalUrl = 'http://example.com/';        
        $job_id = $this->createJobAndGetId($canonicalUrl);        
        
        $ammendment = new Ammendment();
        $ammendment->setJob($this->getJobService()->getById($job_id));
        $ammendment->setReason('url-count-limited');
      
        $this->getManager()->persist($ammendment);
        $this->getManager()->flush();
        
        $this->assertEquals(1, $this->getJobService()->getById($job_id)->getAmmendments()->count());
    }
    
    
    public function testJobAmmendmentCountWithMultipleAmmendments() {
        $canonicalUrl = 'http://example.com/';        
        $job_id = $this->createJobAndGetId($canonicalUrl);
        
        $ammendments = array();
        
        for ($ammendmentIndex = 0; $ammendmentIndex < 10; $ammendmentIndex++) {
            $ammendment = new Ammendment();
            $ammendment->setJob($this->getJobService()->getById($job_id));
            $ammendment->setReason('url-count-limited-' . $ammendmentIndex);            
            $this->getManager()->persist($ammendment);
            $ammendments[] = $ammendment;
        }      
               
        $this->getManager()->flush();
        
        $this->assertEquals(count($ammendments), $this->getJobService()->getById($job_id)->getAmmendments()->count());
    }    
}

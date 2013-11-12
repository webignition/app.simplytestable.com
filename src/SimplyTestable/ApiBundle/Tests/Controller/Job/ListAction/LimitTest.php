<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\ListAction;

class LimitTest extends AbstractListTest {       
    
    public function testNoLimitUsesDefaultLimitOfOne() {
        $jobIds = array();
        
        foreach ($this->getCanonicalUrlCollection(10) as $canonicalUrl) {                        
            $jobIds[] = $this->createJobAndGetId($canonicalUrl);
        }
        
        $list = json_decode($this->getJobController('listAction')->listAction()->getContent());        
        $this->assertEquals(1, count($list->jobs));  
    }
    
    
    public function testZeroLimitUseDefaultLimitOfOne() {
        $jobIds = array();
        
        foreach ($this->getCanonicalUrlCollection(10) as $canonicalUrl) {                        
            $jobIds[] = $this->createJobAndGetId($canonicalUrl);
        }
        
        $list = json_decode($this->getJobController('listAction')->listAction(0)->getContent());        
        $this->assertEquals(1, count($list->jobs));          
    }
    
    
    public function testNegativeLimitUseDefaultLimitOfOne() {
        $jobIds = array();
        
        foreach ($this->getCanonicalUrlCollection(10) as $canonicalUrl) {                        
            $jobIds[] = $this->createJobAndGetId($canonicalUrl);
        }
        
        $list = json_decode($this->getJobController('listAction')->listAction(-1)->getContent());        
        $this->assertEquals(1, count($list->jobs));          
    }
    
    
    public function testLimitOfOneReturnsResultSetContainingOneItem() {
        $jobIds = array();
        
        foreach ($this->getCanonicalUrlCollection(10) as $canonicalUrl) {                        
            $jobIds[] = $this->createJobAndGetId($canonicalUrl);
        }
        
        $list = json_decode($this->getJobController('listAction')->listAction(1)->getContent());        
        $this->assertEquals(1, count($list->jobs));           
    }
    
    
    public function testLimitOfTwoReturnsResultSetContainingTwoItems() {
        $jobIds = array();
        
        foreach ($this->getCanonicalUrlCollection(10) as $canonicalUrl) {                        
            $jobIds[] = $this->createJobAndGetId($canonicalUrl);
        }
        
        $list = json_decode($this->getJobController('listAction')->listAction(2)->getContent());                
        $this->assertEquals(2, count($list->jobs));           
    }    
    
    
    public function testLimitGreaterThanListTotalReturnsListTotalItems() {
        $jobIds = array();
        
        foreach ($this->getCanonicalUrlCollection(10) as $canonicalUrl) {                        
            $jobIds[] = $this->createJobAndGetId($canonicalUrl);
        }
        
        $list = json_decode($this->getJobController('listAction')->listAction(50)->getContent());                
        $this->assertEquals(10, count($list->jobs));           
    }     
    
    
   
    
}



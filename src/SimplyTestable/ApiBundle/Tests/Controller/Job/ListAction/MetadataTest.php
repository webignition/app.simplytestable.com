<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\ListAction;

class MetadataTest extends AbstractListTest {       
    
    public function testMaxResults() {
        $jobTotal = 10;
        
        $jobIds = array();
        
        foreach ($this->getCanonicalUrlCollection($jobTotal) as $canonicalUrl) {                        
            $jobIds[] = $this->createJobAndGetId($canonicalUrl);
        }
        
        $list = json_decode($this->getJobController('listAction')->listAction()->getContent());        
        
        $this->assertEquals($jobTotal, $list->max_results);
        $this->assertEquals(1, count($list->jobs)); 
    }
    
    
    public function testLimit() {
        $jobTotal = 10;
        
        $jobIds = array();
        
        foreach ($this->getCanonicalUrlCollection($jobTotal) as $canonicalUrl) {                        
            $jobIds[] = $this->createJobAndGetId($canonicalUrl);
        }
        
        $list = json_decode($this->getJobController('listAction')->listAction()->getContent());
        $this->assertEquals(1, $list->limit);
    }    
    
    public function testOffset() {
        $jobTotal = 10;
        
        $jobIds = array();
        
        foreach ($this->getCanonicalUrlCollection($jobTotal) as $canonicalUrl) {                        
            $jobIds[] = $this->createJobAndGetId($canonicalUrl);
        }
        
        $list = json_decode($this->getJobController('listAction')->listAction()->getContent());        
        $this->assertEquals(0, $list->offset);
    }
}



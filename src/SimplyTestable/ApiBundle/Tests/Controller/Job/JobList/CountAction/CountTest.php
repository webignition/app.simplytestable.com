<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\CountAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

abstract class CountTest extends BaseControllerJsonTestCase {
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job[]
     */
    protected $jobs = array();
    
    public function setUp() {
        parent::setUp();
        
        $this->createJobs();        
        $this->applyPreListChanges();                 
    }    
 
    abstract protected function getCanonicalUrls();  
    
    protected function applyPreListChanges() {        
    }    
    
    protected function createJobs() {        
        foreach ($this->getCanonicalUrls() as $canonicalUrl) {
            $this->jobs[] = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        }         
    }    
    
    protected function getCanonicalUrlCollection($count = 1) {
        $canonicalUrlCollection = array();
        
        for ($index = 0; $index < $count; $index++) {
            $canonicalUrlCollection[] = 'http://' . ($index + 1) . '.example.com/';
        }
        
        return $canonicalUrlCollection;
    }      
    
}
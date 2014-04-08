<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction;

abstract class ListContentTest extends SingleListTest { 
    
    abstract protected function getExpectedListLength();
    abstract protected function getExpectedJobListUrls();
    
    public function testListLength() {         
        $this->assertEquals($this->getExpectedListLength(), count($this->list->jobs));
    }
    
    public function testExpectedJobUrls() {        
        foreach ($this->getExpectedJobListUrls() as $index => $canonicalUrl) {
            $this->assertEquals($canonicalUrl, $this->list->jobs[$index]->website);
        }         
    } 
    
}
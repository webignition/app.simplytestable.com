<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\CountAction;

abstract class ContentTest extends SingleUserTest { 
    
    abstract protected function getExpectedCountValue();
    
    public function testCountValue() {       
        $this->assertEquals($this->getExpectedCountValue(), $this->count);
    }    
}
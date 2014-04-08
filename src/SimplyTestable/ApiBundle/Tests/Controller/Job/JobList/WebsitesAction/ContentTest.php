<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction;

abstract class ContentTest extends SingleUserTest { 
    
    abstract protected function getExpectedWebsitesList();
    
    public function testWebsiteList() {       
        $this->assertEquals($this->getExpectedWebsitesList(), $this->list);
    }    
}
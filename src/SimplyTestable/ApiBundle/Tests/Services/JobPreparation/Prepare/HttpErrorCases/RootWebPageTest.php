<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\HttpErrorCases;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class RootWebPageTest extends BaseSimplyTestableTestCase {   

    public function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createAndResolveDefaultJob());
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',            
            'HTTP/1.0 ' . $this->getTestStatusCode(),
        )));
        
        $this->getJobPreparationService()->prepare($job);
        
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState(), $job->getState());
    }
    
    
    public function test400() {}
    public function test404() {}
    public function test500() {}
    public function test503() {}
    
    
    private function getTestStatusCode() {
        return (int)  str_replace('test', '', $this->getName());
    }

}

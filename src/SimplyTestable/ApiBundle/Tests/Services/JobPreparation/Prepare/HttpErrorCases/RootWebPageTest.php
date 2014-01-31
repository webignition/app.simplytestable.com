<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\HttpErrorCases;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class RootWebPageTest extends BaseSimplyTestableTestCase {   
    
    const CANONICAL_URL = 'http://example.com';     
    
    public function setUp() {
        parent::setUp();
        
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',            
            'HTTP/1.0 ' . $this->getTestStatusCode(),
        )));        
        
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL));
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

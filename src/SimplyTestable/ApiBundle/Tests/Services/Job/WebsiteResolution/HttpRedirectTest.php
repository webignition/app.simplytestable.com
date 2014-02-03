<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class HttpRedirectTest extends BaseSimplyTestableTestCase {    
    
    const SOURCE_URL = 'http://example.com';
    const EFFECTIVE_URL = 'http://foo.example.com/';
    
    public function setUp() {
        parent::setUp();
        
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 " . $this->getStatusCode() . "\nLocation:" . self::EFFECTIVE_URL,
            "HTTP/1.0 200 OK"
        )));
        
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::SOURCE_URL)); 
        
        $this->getJobWebsiteResolutionService()->resolve($job);
        $this->assertEquals(self::EFFECTIVE_URL, $job->getWebsite()->getCanonicalUrl());
    }
    

    public function test301() {}
    public function test302() {}
    public function test303() {}
    public function test307() {}
    public function test308() {}
    
    
    /**
     * 
     * @return int
     */
    private function getStatusCode() {
        return (int)  str_replace('test', '', $this->getName());
    }
}

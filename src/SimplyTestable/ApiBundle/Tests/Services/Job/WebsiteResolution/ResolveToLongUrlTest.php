<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class ResolveToLongUrlTest extends BaseSimplyTestableTestCase {    
    
    const SOURCE_URL = 'http://example.com/';
    const EFFECTIVE_URL = 'http://example.com/123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890';
    
    public function setUp() {
        parent::setUp();
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.1 301\nLocation: " . self::EFFECTIVE_URL,
            "HTTP/1.0 200"
        )));  
    }
    
    public function testTest() {      
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::SOURCE_URL, null, 'single url'));
        $this->resolveJob($job->getWebsite()->getCanonicalUrl(), $job->getId());
        
        $this->assertEquals(self::EFFECTIVE_URL, $job->getWebsite()->getCanonicalUrl());    
    }  

}

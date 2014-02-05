<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class TrimToRootUrlTest extends BaseSimplyTestableTestCase {    
    
    const SOURCE_URL = 'http://example.com/';
    const EFFECTIVE_URL = 'http://www.example.com/relative/path.html';
    const ROOT_URL = 'http://www.example.com/';
    
    public function setUp() {
        parent::setUp();
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.1 301\nLocation: http://www.example.com/",
            "HTTP/1.1 302 Found\nLocation: /relative/path.html",
            "HTTP/1.0 200"
        )));  
    }
    
    public function testFullSiteTestResolvesToRootUrl() {        
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::SOURCE_URL));
        $this->resolveJob($job->getWebsite()->getCanonicalUrl(), $job->getId());
        
        $this->assertEquals(self::ROOT_URL, $job->getWebsite()->getCanonicalUrl());    
    } 
    
    
    public function testSingleUrlTestResolvesToEffectiveUrl() {        
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::SOURCE_URL, null, 'single url'));
        $this->resolveJob($job->getWebsite()->getCanonicalUrl(), $job->getId());
        
        $this->assertEquals(self::EFFECTIVE_URL, $job->getWebsite()->getCanonicalUrl());    
    }    

}

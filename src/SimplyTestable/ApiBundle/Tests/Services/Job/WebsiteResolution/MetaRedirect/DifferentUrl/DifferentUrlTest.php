<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution\MetaRedirect\DifferentUrl;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

abstract class DifferentUrlTest extends BaseSimplyTestableTestCase {    
    
    const SOURCE_URL = 'http://example.com/';
    
    public function setUp() {
        parent::setUp();
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=" . $this->getRedirectUrl() . "\"></head></html>",
            "HTTP/1.0 200 OK",
            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=" . $this->getRedirectUrl() . "\"></head></html>",
            "HTTP/1.0 200 OK"            
        )));
        
        $fullSiteJob = $this->getJobService()->getById($this->createJobAndGetId(self::SOURCE_URL)); 
        $this->getJobWebsiteResolutionService()->resolve($fullSiteJob);
        $this->assertEquals($this->getRootUrl(), $fullSiteJob->getWebsite()->getCanonicalUrl());
        
        $singleUrlJob = $this->getJobService()->getById($this->createJobAndGetId(self::SOURCE_URL, null, 'single url')); 
        $this->getJobWebsiteResolutionService()->resolve($singleUrlJob);
        $this->assertEquals($this->getEffectiveUrl(), $singleUrlJob->getWebsite()->getCanonicalUrl());        
    }    

    abstract protected function getRedirectUrl(); 
    abstract protected function getEffectiveUrl(); 
    abstract protected function getRootUrl();
}

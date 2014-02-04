<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution\MetaRedirect\DifferentUrl;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

abstract class DifferentUrlTest extends BaseSimplyTestableTestCase {    
    
    const SOURCE_URL = 'http://example.com/';
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    private $job;
    
    public function setUp() {
        parent::setUp();
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=" . $this->getRedirectUrl() . "\"></head></html>",
            "HTTP/1.0 200 OK"
        )));
        
        $this->job = $this->getJobService()->getById($this->createJobAndGetId(self::SOURCE_URL)); 
        $this->getJobWebsiteResolutionService()->resolve($this->job);
        $this->assertEquals($this->getEffectiveUrl(), $this->job->getWebsite()->getCanonicalUrl());
    }    

    abstract protected function getRedirectUrl(); 
    abstract protected function getEffectiveUrl(); 
}

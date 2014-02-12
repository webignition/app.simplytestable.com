<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class HttpAuthTest extends BaseSimplyTestableTestCase {    
    
    const SOURCE_URL = 'http://example.com/';
    
    public function setUp() {
        parent::setUp();
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200"
        )));  
    }
    
    public function testJobHttpAuthParametersArePassedToUrlResolver() {        
        $username = 'example';
        $password = 'password';
        
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::SOURCE_URL, null, null, null, null, array(
            'http-auth-username' => $username,
            'http-auth-password' => $password
        )));
        $this->resolveJob($job->getWebsite()->getCanonicalUrl(), $job->getId());        
        
        /* @var $urlResolverBaseRequestCurlOptions \Guzzle\Common\Collection */
        $urlResolverBaseRequestCurlOptions = $this->getJobWebsiteResolutionService()->getUrlResolver($job)->getConfiguration()->getBaseRequest()->getCurlOptions();
        
        $this->assertTrue($urlResolverBaseRequestCurlOptions->hasKey(CURLOPT_USERPWD));
        $this->assertEquals($username . ':' . $password, $urlResolverBaseRequestCurlOptions->get(CURLOPT_USERPWD));
    } 
}

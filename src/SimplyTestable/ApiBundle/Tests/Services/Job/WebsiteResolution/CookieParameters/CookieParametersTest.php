<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution\CookieParameters;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class CookieParametersTest extends BaseSimplyTestableTestCase {    
    
    const SOURCE_URL = 'http://example.com/';
    
    private $cookies = array(
        array(
            'domain' => '.example.com',
            'name' => 'foo',
            'value' => 'bar'
        )               
    );
    
    private $job;
    
    public function setUp() {
        parent::setUp();
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200"
        )));  
        
        $this->job = $this->getJobService()->getById($this->createJobAndGetId(self::SOURCE_URL, null, null, null, null, array(
            'cookies' => json_encode($this->cookies)
        )));
        
        $this->resolveJob($this->job->getWebsite()->getCanonicalUrl(), $this->job->getId());
    }
    
    public function testCookieParametersArePassedToUrlResolver() {                
        $this->assertEquals($this->cookies, $this->getJobWebsiteResolutionService()->getUrlResolver($this->job)->getConfiguration()->getCookies());
    } 
    
    public function testCookieParametersAreUsedByUrlResolver() {
        $this->assertEquals($this->getExpectedCookieValues(), $this->getHttpClientService()->getHistoryPlugin()->getLastRequest()->getCookies());
    }  
    
    /**
     * 
     * @return array
     */
    private function getExpectedCookieValues() {
        $nameValueArray = array();
        
        foreach ($this->cookies as $cookie) {
            $nameValueArray[$cookie['name']] = $cookie['value'];
        }
        
        return $nameValueArray;
    }    
}

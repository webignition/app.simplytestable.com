<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution\CurlRejection;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

abstract class CurlRejectionTest extends BaseSimplyTestableTestCase {     
    
    const SOURCE_URL = 'http://example.com/';
    
    protected $job;
    
    public function setUp() {
        parent::setUp();
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "CURL/" . $this->getTestStatusCode()
        )));
        
        $this->job = $this->getJobService()->getById($this->createJobAndGetId(self::SOURCE_URL));

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->resolveJob($this->job->getWebsite()->getCanonicalUrl(), $this->job->getId());               
    }
    
    public function test7() {}
    public function test28() {}
    public function test52() {}
    
    /**
     * 
     * @return int
     */
    protected function getTestStatusCode() {
        return (int)str_replace('test', '', $this->getName());
    }

}

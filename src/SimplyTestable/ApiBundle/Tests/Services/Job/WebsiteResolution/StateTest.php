<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class StateTest extends BaseSimplyTestableTestCase {    
    
    const SOURCE_URL = 'http://example.com';
    
    public function setUp() {
        parent::setUp();
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK"
        )));
    }
    

    public function testFullSiteJobStateIsResolved() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::SOURCE_URL)); 
        
        $this->getJobWebsiteResolutionService()->resolve($job);
        $this->assertEquals($this->getJobService()->getResolvedState(), $job->getState());
    }
    
    public function testSingleUrlJobStateIsResolved() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::SOURCE_URL, null, 'single url')); 
        
        $this->getJobWebsiteResolutionService()->resolve($job);        
        $this->assertEquals($this->getJobService()->getResolvedState(), $job->getState());
    }
    

}

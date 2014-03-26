<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class CUrlOptionsAreSetOnRequestsTest extends BaseSimplyTestableTestCase {    
    
    const SOURCE_URL = 'http://example.com';
    
    public function setUp() {
        parent::setUp();
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK"
        )));
        
        $this->getJobWebsiteResolutionService()->resolve($this->getJobService()->getById($this->createJobAndGetId(self::SOURCE_URL)));
    }
    
    public function testCurlOptionsAreSetOnAllRequests() {
        $this->assertSystemCurlOptionsAreSetOnAllRequests();
    }

}

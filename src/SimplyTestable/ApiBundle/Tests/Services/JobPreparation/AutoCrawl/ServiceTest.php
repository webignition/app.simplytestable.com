<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\AutoCrawl;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

/**
 * Test cases were a crawl job should and shouldn't be created
 */
class ServiceTest extends BaseSimplyTestableTestCase {    
    
    const CANONICAL_URL = 'http://example.com';
    
    public function setUp() {
        parent::setUp();
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(). '/HttpResponses'));
    } 
    
    
    public function testPublicUserJobDoesNotAutostartCrawlJob() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL));
        $this->getJobPreparationService()->prepare($job);
        
        $this->assertFalse($this->getCrawlJobContainerService()->hasForJob($job));
    }

    
    public function testNonPublicUserJobDoesAutostartCrawlJob() {
        $user = $this->createAndActivateUser('user@example.com', 'password');
        
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL, $user->getEmail()));
        $this->getJobPreparationService()->prepare($job);
        
        $this->assertTrue($this->getCrawlJobContainerService()->hasForJob($job));
    } 

}

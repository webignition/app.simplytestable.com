<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\AutoCrawl;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

/**
 * Test cases were a crawl job should and shouldn't be created
 */
class ServiceTest extends BaseSimplyTestableTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->queueResolveHttpFixture();        
        $this->queuePrepareHttpFixturesForCrawlJob(self::DEFAULT_CANONICAL_URL);        
    } 
    
    
    public function testPublicUserJobDoesNotAutostartCrawlJob() {        
        $job = $this->getJobService()->getById($this->createAndResolveDefaultJob());
        $this->getJobPreparationService()->prepare($job);
        
        $this->assertFalse($this->getCrawlJobContainerService()->hasForJob($job));
    }

    
    public function testNonPublicUserJobDoesAutostartCrawlJob() {
        $user = $this->createAndActivateUser('user@example.com', 'password');
        
        $job = $this->getJobService()->getById($this->createAndResolveJob(self::DEFAULT_CANONICAL_URL, $user->getEmail()));
        $this->getJobPreparationService()->prepare($job);
        
        $this->assertTrue($this->getCrawlJobContainerService()->hasForJob($job));
    } 

}

<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class ServiceTest extends BaseSimplyTestableTestCase {    
    
    const EXPECTED_TASK_TYPE_COUNT = 4;
    const CANONICAL_URL = 'http://example.com';
    
    public function setUp() {
        parent::setUp();        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName()). '/HttpResponses'));
    }
    
    
    public function testHandleSitemapContainingSchemelessUrls() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL));        
        $this->getJobPreparationService()->prepare($job);        
        
        $this->assertTrue($job->getTasks()->isEmpty());
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState(), $job->getState());
    }

    
    public function testHandleSingleIndexLargeSitemap() {
        $this->getWebSiteService()->getSitemapFinder()->getSitemapRetriever()->setTotalTransferTimeout(0.00001);
        
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL));        
        $this->getJobPreparationService()->prepare($job);         
        
        $expectedUrlCount = $this->getUserAccountPlanService()->getForUser($this->getUserService()->getPublicUser())->getPlan()->getConstraintNamed('urls_per_job')->getLimit();
        
        $this->assertEquals(self::EXPECTED_TASK_TYPE_COUNT * $expectedUrlCount, $job->getTasks()->count());
    } 

    
    public function testHandleLargeCollectionOfSitemaps() {        
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL));        
        $this->getJobPreparationService()->prepare($job);         
        
        $expectedUrlCount = $this->getUserAccountPlanService()->getForUser($this->getUserService()->getPublicUser())->getPlan()->getConstraintNamed('urls_per_job')->getLimit();
        
        $this->assertEquals(self::EXPECTED_TASK_TYPE_COUNT * $expectedUrlCount, $job->getTasks()->count());
    } 
    
 
    public function testHandleMalformedRssUrl() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL));        
        $this->getJobPreparationService()->prepare($job);         
        
        $this->assertTrue($job->getTasks()->isEmpty());
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState(), $job->getState());    
    }
    
    
    public function testCrawlJobTakesParametersOfParentJob() {
        $user = $this->createAndActivateUser('user@example.com', 'password');
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL, $user->getEmail(), 'full site', array('HTML validation'), null, array(
            'http-auth-username' => 'example',
            'http-auth-password' => 'password'
        )));        
      
        $this->getJobPreparationService()->prepare($job);
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->assertEquals($crawlJobContainer->getParentJob()->getParameters(), $crawlJobContainer->getCrawlJob()->getParameters());
    }     

}

<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class ServiceTest extends BaseSimplyTestableTestCase {    
    
    const EXPECTED_TASK_TYPE_COUNT = 4;    
    
    public function testHandleSitemapContainingSchemelessUrls() {
        $job = $this->getJobService()->getById($this->createAndResolveDefaultJob());
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));
        $this->getJobPreparationService()->prepare($job);        
        
        $this->assertTrue($job->getTasks()->isEmpty());
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState(), $job->getState());
    }

    
    public function testHandleSingleIndexLargeSitemap() {
        $job = $this->getJobService()->getById($this->createAndResolveDefaultJob());
        
        $this->getWebSiteService()->getSitemapFinder()->getSitemapRetriever()->setTotalTransferTimeout(0.00001);
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));       
        $this->getJobPreparationService()->prepare($job);         
        
        $expectedUrlCount = $this->getUserAccountPlanService()->getForUser($this->getUserService()->getPublicUser())->getPlan()->getConstraintNamed('urls_per_job')->getLimit();
        
        $this->assertEquals(self::EXPECTED_TASK_TYPE_COUNT * $expectedUrlCount, $job->getTasks()->count());
    } 

    
    public function testHandleLargeCollectionOfSitemaps() {        
        $job = $this->getJobService()->getById($this->createAndResolveDefaultJob());
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));       
        $this->getJobPreparationService()->prepare($job);         
        
        $expectedUrlCount = $this->getUserAccountPlanService()->getForUser($this->getUserService()->getPublicUser())->getPlan()->getConstraintNamed('urls_per_job')->getLimit();
        
        $this->assertEquals(self::EXPECTED_TASK_TYPE_COUNT * $expectedUrlCount, $job->getTasks()->count());
    } 
    
 
    public function testHandleMalformedRssUrl() {
        $job = $this->getJobService()->getById($this->createAndResolveDefaultJob());
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));
        $this->getJobPreparationService()->prepare($job);         
        
        $this->assertTrue($job->getTasks()->isEmpty());
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState(), $job->getState());    
    }
    
    
    public function testCrawlJobTakesParametersOfParentJob() {        
        $job = $this->getJobService()->getById($this->createAndResolveJob(
                self::DEFAULT_CANONICAL_URL,
                $this->getTestUser()->getEmail(),
                'full site',
                array('HTML validation'),
                null,
                array(
                    'http-auth-username' => 'example',
                    'http-auth-password' => 'password'
                )
        ));
        
        $this->queuePrepareHttpFixturesForCrawlJob($job->getWebsite()->getCanonicalUrl());
        $this->getJobPreparationService()->prepare($job);
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->assertEquals($crawlJobContainer->getParentJob()->getParameters(), $crawlJobContainer->getCrawlJob()->getParameters());
    }     

}
